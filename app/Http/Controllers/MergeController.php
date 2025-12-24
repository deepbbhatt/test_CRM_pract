<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\CustomFieldValue;
use App\Models\MergeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MergeController extends Controller
{
    /**
     * Return modal HTML for selecting master & secondary.
     * (Used via fetch('/merge/modal'))
     */
    public function showMergeModal()
    {
        $contacts = Contact::where('is_merged', false)->get();
        return view('contacts.partials.merge_modal', compact('contacts'));
    }

    /**
     * Perform the merge: master_id & secondary_id in request.
     */
    public function merge(Request $request)
    {
        $data = $request->validate([
            'master_id' => 'required|exists:contacts,id',
            'secondary_id' => 'required|exists:contacts,id|different:master_id'
        ]);

        $master = Contact::findOrFail($data['master_id']);
        $secondary = Contact::findOrFail($data['secondary_id']);

        DB::beginTransaction();
        try {
            $changes = [
                'emails' => [],
                'phones' => [],
                'custom_fields' => [],
                'notes' => []
            ];

            // --- Emails & Phones: preserve extras ---
            // If contacts table has additional_emails/additional_phones JSON columns, use them.
            // We'll gather existing arrays (or empty) then append new unique values.
            $masterEmails = [];
            $masterPhones = [];
            if (!empty($master->email)) $masterEmails[] = $master->email;
            if (!empty($secondary->email) && $secondary->email !== $master->email) {
                $masterEmails[] = $secondary->email;
                $changes['emails'][] = $secondary->email;
            }
            // include existing additional_emails (if column exists)
            if (isset($master->additional_emails) && is_array($master->additional_emails)) {
                $masterEmails = array_merge($masterEmails, $master->additional_emails);
            }
            // dedupe
            $masterEmails = array_values(array_unique(array_filter($masterEmails)));

            $masterPhones = [];
            if (!empty($master->phone)) $masterPhones[] = $master->phone;
            if (!empty($secondary->phone) && $secondary->phone !== $master->phone) {
                $masterPhones[] = $secondary->phone;
                $changes['phones'][] = $secondary->phone;
            }
            if (isset($master->additional_phones) && is_array($master->additional_phones)) {
                $masterPhones = array_merge($masterPhones, $master->additional_phones);
            }
            $masterPhones = array_values(array_unique(array_filter($masterPhones)));

            // Option A: if you added JSON cols to contacts, update it
            if (isset($master->additional_emails)) {
                // store full list but keep primary in 'email' field
                $primaryEmail = $masterEmails[0] ?? null;
                $otherEmails = $masterEmails;
                if ($primaryEmail) {
                    // remove primary from additional array (optional)
                    $otherEmails = array_values(array_diff($otherEmails, [$primaryEmail]));
                }
                $master->email = $primaryEmail;
                $master->additional_emails = !empty($otherEmails) ? array_values($otherEmails) : null;
            } else {
                // If no JSON column, just log them
                if (!empty($changes['emails'])) {
                    $changes['notes'][] = 'Extra emails: ' . json_encode($changes['emails']);
                }
            }

            if (isset($master->additional_phones)) {
                $primaryPhone = $masterPhones[0] ?? null;
                $otherPhones = $masterPhones;
                if ($primaryPhone) {
                    $otherPhones = array_values(array_diff($otherPhones, [$primaryPhone]));
                }
                $master->phone = $primaryPhone;
                $master->additional_phones = !empty($otherPhones) ? array_values($otherPhones) : null;
            } else {
                if (!empty($changes['phones'])) {
                    $changes['notes'][] = 'Extra phones: ' . json_encode($changes['phones']);
                }
            }

            $master->save();

            // --- Custom fields merge policy ---
            // For each custom field of the secondary:
            //  - if master does not have it, copy the secondary's value to master
            //  - if both have different non-empty values, store both values in master as JSON array

            $secondaryValues = $secondary->customValues()->get(); // collection of CustomFieldValue
            foreach ($secondaryValues as $sv) {
                $fieldId = $sv->field_definition_id;

                $mf = CustomFieldValue::where('contact_id', $master->id)
                    ->where('field_definition_id', $fieldId)
                    ->first();

                if (!$mf) {
                    // simply reassign or duplicate — we choose to duplicate (create new)
                    CustomFieldValue::create([
                        'contact_id' => $master->id,
                        'field_definition_id' => $fieldId,
                        'value_text' => $sv->value_text,
                        'value_file_path' => $sv->value_file_path
                    ]);
                    $changes['custom_fields'][] = [
                        'action' => 'copied',
                        'field_id' => $fieldId,
                        'value' => $sv->value_text ?? $sv->value_file_path
                    ];
                } else {
                    // conflict: both exist
                    $a = $mf->value_text;
                    $b = $sv->value_text;

                    // file fields: if both file paths and different, keep both by storing JSON of paths (or you may keep both rows).
                    if ($mf->value_file_path || $sv->value_file_path) {
                        // combine file paths into JSON list if differ
                        $existing = $mf->value_file_path ? (array)$mf->value_file_path : [];
                        if ($sv->value_file_path && $sv->value_file_path !== $mf->value_file_path) {
                            $existing = array_values(array_unique(array_merge((array)$existing, [$sv->value_file_path])));
                        }
                        // save as JSON string in value_text OR replace value_file_path with first, and mirror others into value_text as JSON — choose approach consistent with your app
                        $mf->value_text = json_encode($existing);
                        $mf->value_file_path = $existing[0] ?? null;
                        $mf->save();
                        $changes['custom_fields'][] = [
                            'action' => 'file_merged',
                            'field_id' => $fieldId,
                            'values' => $existing
                        ];
                    } else {
                        // text fields: if different and non-empty, convert to JSON array with both values (unique)
                        if ($a !== $b) {
                            $vals = [];
                            // if $a already JSON array, decode
                            $adecode = @json_decode($a, true);
                            if (is_array($adecode)) $vals = array_merge($vals, $adecode);
                            elseif ($a !== null && $a !== '') $vals[] = $a;

                            $bdecode = @json_decode($b, true);
                            if (is_array($bdecode)) $vals = array_merge($vals, $bdecode);
                            elseif ($b !== null && $b !== '') $vals[] = $b;

                            $vals = array_values(array_unique(array_filter($vals)));
                            $mf->value_text = json_encode($vals);
                            $mf->save();

                            $changes['custom_fields'][] = [
                                'action' => 'merged_text',
                                'field_id' => $fieldId,
                                'values' => $vals
                            ];
                        } else {
                            // same value: nothing to do
                        }
                    }
                }
            }

            // --- mark secondary as merged (do NOT delete) ---
            $secondary->is_merged = true;
            $secondary->merged_into = $master->id;
            $secondary->save();

            // --- log what happened for audit / UI ---
            MergeLog::create([
                'master_contact_id' => $master->id,
                'secondary_contact_id' => $secondary->id,
                'changes' => $changes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contacts merged successfully',
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Merge failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
