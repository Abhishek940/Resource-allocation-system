<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employeeName' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'department' =>'required|string|max:255',
            'location' => 'required|string|max:255',
            'primaryskill' => 'required|string|max:255',
            'proficiency' => 'required|string|max:255',
             'experience' => 'required|max:255',
             'secondarySkills' => 'required|string|max:255',
             'tertiarySkills' => 'required|string|max:255',
             'assessmentScore' => 'required|max:255',
             'validationStatus' => 'required|string|max:255',
             'certifications' => 'required|string|max:255',
             'resourceStatus' => 'required|string|max:255',
             'availableFrom' => 'required|string|max:255',
             'projectExperience' => 'required|max:255',
            
        ];
    }
}
