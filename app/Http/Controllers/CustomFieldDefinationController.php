<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldDefination;
use Illuminate\Http\Request;

class CustomFieldDefinationController extends Controller
{
    public function index()
    {
        $fields = CustomFieldDefination::orderBy('id')->get();
        return view('custom_fields.index', compact('fields'));
    }

    public function create()
    {
        return view('custom_fields.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:customfield_definations,slug',
            'type' => 'required|string',
            'options' => 'nullable|string', // JSON or comma-separated
            'validation' => 'nullable|string'
        ]);

        if ($data['options']) {
            // accept comma-separated or JSON array
            $options = @json_decode($data['options'], true);
            if (!is_array($options)) {
                $options = array_map('trim', explode(',', $data['options']));
            }
            $data['options'] = $options;
        } else {
            $data['options'] = null;
        }

        CustomFieldDefination::create($data);
        return redirect()->route('custom-fields.index')->with('success','Field created');
    }

    public function edit(CustomFieldDefination $custom_field)
    {
        return view('custom_fields.edit', ['field' => $custom_field]);
    }

    public function update(Request $request, CustomFieldDefination $custom_field)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:customfield_definations,slug,'.$custom_field->id,
            'type' => 'required|string',
            'options' => 'nullable|string',
            'validation' => 'nullable|string'
        ]);

        if ($data['options']) {
            $options = @json_decode($data['options'], true);
            if (!is_array($options)) {
                $options = array_map('trim', explode(',', $data['options']));
            }
            $data['options'] = $options;
        } else {
            $data['options'] = null;
        }

        $custom_field->update($data);
        return redirect()->route('custom-fields.index')->with('success','Field updated');
    }

    public function destroy(CustomFieldDefination $custom_field)
    {
        $custom_field->delete();
        return redirect()->route('custom-fields.index')->with('success','Field deleted');
    }
}
