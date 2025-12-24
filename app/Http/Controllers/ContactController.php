<?php

namespace App\Http\Controllers;

use App\Models\ContactMerge;
use App\Models\Contacts;
use App\Models\CustomFieldDefination;
use App\Models\CustomFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function index()
    {
        $fields = CustomFieldDefination::orderBy('id')->get();
        $contacts = Contacts::orderBy('created_at','desc')->paginate(10);
        return view('contacts.index', compact('contacts','fields'));
    }

    // AJAX: filtered list (returns partial HTML)
    public function ajaxList(Request $request)
    {
        $query = Contacts::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('custom_field_id') && $request->filled('custom_field_value')) {
            $cfId = (int)$request->custom_field_id;
            $val = $request->custom_field_value;
            $query->whereHas('customValues', function($q) use ($cfId, $val) {
                $q->where('field_definition_id', $cfId)
                  ->where('value_text', 'like', "%$val%");
            });
        }

        $contacts = $query->orderBy('created_at','desc')->paginate(10);
        $html = view('contacts.partials.table_rows', compact('contacts'))->render();
        $pagination = view('contacts.partials.pagination', compact('contacts'))->render();

        return response()->json(['success'=>true, 'html'=>$html, 'pagination'=>$pagination]);
    }
public function create()
{
    $fields = CustomFieldDefination::all();
    return response()->json([
        'success' => true,
        'data' => compact('fields')
    ]);
}

    // AJAX: create
    public function store(Request $request)
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,other',
            'profile_image' => 'nullable|image|max:2048',
            'additional_file' => 'nullable|file|max:5120',
        ];

        // Collect custom field definitions to validate them if definitions have validation rules
        $fields = CustomFieldDefination::all();
        $customRules = [];
        foreach ($fields as $f) {
            if ($f->validation) {
                // custom fields are received as custom[<id>]
                $customRules['custom.'.$f->id] = $f->validation;
            }
            if ($f->type === 'file') {
                $customRules['custom_files.'.$f->id] = 'nullable|file|max:5120';
            }
        }

        $rules = array_merge($baseRules, $customRules);
        $validated = $request->validate($rules);

        // Handle files
        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('contacts/profile','public');
        }
        if ($request->hasFile('additional_file')) {
            $validated['additional_file_path'] = $request->file('additional_file')->store('contacts/files','public');
        }

        $contact = Contacts::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'profile_image_path' => $validated['profile_image_path'] ?? null,
            'additional_file_path' => $validated['additional_file_path'] ?? null,
        ]);

        // store custom values
        $custom = $request->input('custom', []);
        $customFiles = $request->file('custom_files', []);
        foreach ($custom as $fieldId => $value) {
            $fieldDef = $fields->where('id', $fieldId)->first();
            if (!$fieldDef) continue;
            $data = ['contact_id'=>$contact->id, 'field_definition_id'=>$fieldDef->id];
            if ($fieldDef->type === 'file' && isset($customFiles[$fieldId])) {
                $data['value_file_path'] = $customFiles[$fieldId]->store('contacts/custom','public');
            } else {
                $data['value_text'] = is_array($value) ? json_encode($value) : $value;
            }
            CustomFieldValue::create($data);
        }

        return response()->json(['success'=>true, 'message'=>'Contact created']);
    }

public function edit($id)
{
    $contact = Contacts::with('customValues')->findOrFail($id);

    $custom = [];
    foreach ($contact->customValues as $cv) {
        $custom[$cv->field_definition_id] =
            $cv->value_text ?? $cv->value_file_path;
    }

    return response()->json([
        'success' => true,
        'data' => [
            'contact' => $contact,
            'custom' => $custom
        ]
    ]);
}


    // AJAX: update
    public function update(Request $request, Contacts $contact)
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,other',
            'profile_image' => 'nullable|image|max:2048',
            'additional_file' => 'nullable|file|max:5120',
        ];

        $fields = CustomFieldDefination::all();
        $customRules = [];
        foreach ($fields as $f) {
            if ($f->validation) {
                $customRules['custom.'.$f->id] = $f->validation;
            }
            if ($f->type === 'file') {
                $customRules['custom_files.'.$f->id] = 'nullable|file|max:5120';
            }
        }

        $rules = array_merge($baseRules, $customRules);
        $validated = $request->validate($rules);

        // Files: delete old if replaced
        if ($request->hasFile('profile_image')) {
            if ($contact->profile_image_path) Storage::disk('public')->delete($contact->profile_image_path);
            $contact->profile_image_path = $request->file('profile_image')->store('contacts/profile','public');
        }
        if ($request->hasFile('additional_file')) {
            if ($contact->additional_file_path) Storage::disk('public')->delete($contact->additional_file_path);
            $contact->additional_file_path = $request->file('additional_file')->store('contacts/files','public');
        }

        $contact->name = $validated['name'];
        $contact->email = $validated['email'] ?? null;
        $contact->phone = $validated['phone'] ?? null;
        $contact->gender = $validated['gender'] ?? null;
        $contact->save();

        // handle custom fields
        $custom = $request->input('custom', []);
        $customFiles = $request->file('custom_files', []);

        foreach ($fields as $fieldDef) {
            $fid = $fieldDef->id;
            $cfv = CustomFieldValue::firstOrNew([
                'contact_id' => $contact->id,
                'field_definition_id' => $fid
            ]);
            if ($fieldDef->type === 'file') {
                if (isset($customFiles[$fid])) {
                    if ($cfv->value_file_path) Storage::disk('public')->delete($cfv->value_file_path);
                    $cfv->value_file_path = $customFiles[$fid]->store('contacts/custom','public');
                    $cfv->value_text = null;
                    $cfv->save();
                }
                // else leave existing file value as is
            } else {
                if (array_key_exists($fid, $custom)) {
                    $cfv->value_text = is_array($custom[$fid]) ? json_encode($custom[$fid]) : $custom[$fid];
                    $cfv->value_file_path = null;
                    $cfv->save();
                }
            }
        }

        return response()->json(['success'=>true, 'message'=>'Contact updated']);
    }

    // AJAX: delete
    public function destroy(Contacts $contact)
    {
        if ($contact->profile_image_path) Storage::disk('public')->delete($contact->profile_image_path);
        if ($contact->additional_file_path) Storage::disk('public')->delete($contact->additional_file_path);

        // delete custom files
        foreach ($contact->customValues as $cv) {
            if ($cv->value_file_path) Storage::disk('public')->delete($cv->value_file_path);
        }

        $contact->delete();
        return response()->json(['success'=>true, 'message'=>'Contact deleted']);
    }
    

 public function merge(Request $request)
{
    $request->validate([
        'master_id' => 'required|exists:contacts,id',
        'secondary_id' => 'required|exists:contacts,id|different:master_id',
    ]);

    DB::transaction(function () use ($request) {

        $master = Contacts::findOrFail($request->master_id);
        $secondary = Contacts::with('customValues')->findOrFail($request->secondary_id);

        /* -----------------------------------
           STORE EXTRA EMAIL & PHONE
        ----------------------------------- */
        ContactMerge::create([
            'master_contact_id' => $master->id,
            'secondary_contact_id' => $secondary->id,
            'extra_email' => $secondary->email !== $master->email
                            ? $secondary->email : null,
            'extra_phone' => $secondary->phone !== $master->phone
                            ? $secondary->phone : null,
        ]);

        /* -----------------------------------
           MERGE CUSTOM FIELDS
        ----------------------------------- */
        foreach ($secondary->customValues as $value) {

            $exists = CustomFieldValue::where('contact_id', $master->id)
                ->where('field_definition_id', $value->field_definition_id)
                ->exists();

            if (!$exists) {
                $value->update([
                    'contact_id' => $master->id
                ]);
            }
        }

        /* -----------------------------------
           MARK SECONDARY AS MERGED
        ----------------------------------- */
        $secondary->update([
            'is_active' => 1,
            'merged_id' => $master->id
        ]);
    });

    return response()->json([
        'success' => true,
        'message' => 'Contacts merged successfully'
    ]);
}

public function listJson()
{
    $contacts = Contacts::orderBy('created_at', 'desc')
                        ->get( );

        return response()->json(['contacts' => $contacts]);
}

}
