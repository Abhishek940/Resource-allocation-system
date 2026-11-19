<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
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
        'projectName' => 'required|string|max:150',
        'project_des' => 'nullable|string',
        'businessDomain' => 'nullable|string|max:100',
        'requirementOwner' => 'nullable|string|max:100',

        'requirementPriority' => 'required|',

        'location' => 'nullable|string|max:150',
        'duration' => 'nullable',

        'requiredTechnology' => 'nullable|string',
        'mandatorySkills' => 'nullable|string',
        'preferredSkills' => 'nullable|string',

        'minProficiency' => 'nullable',
        'minExperience' => 'nullable|',

        'numberOfResources' => 'required|integer|min:1',
        'startFrom' => 'nullable|date_format:Y-m-d',
        'availabilityDuration' => 'nullable',
        'requiredAvailability' => 'nullable|string|max:80',
        'projectDuration' => 'nullable',

        'status' => 'required|integer|in:1,2,3,4,5',
    ];

    }
}
