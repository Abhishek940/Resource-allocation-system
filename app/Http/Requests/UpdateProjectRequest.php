<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
 
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'projectName'          => 'sometimes|nullable|string|max:150',
            'project_des'          => 'sometimes|nullable|string',
            'businessDomain'       => 'sometimes|nullable|string|max:100',
            'requirementOwner'     => 'sometimes|nullable|',
            'requirementPriority'  => 'sometimes|nullable|',
            'location'             => 'sometimes|nullable|string|max:150',
            'duration'             => 'sometimes|nullable|',
            'requiredTechnology'   => 'sometimes|nullable|string',
            'mandatorySkills'      => 'sometimes|nullable|string',
            'preferredSkills'      => 'sometimes|nullable|string',
            'minProficiency'       => 'sometimes|nullable|',
            'minExperience'        => 'sometimes|nullable|',
            'numberOfResources'    => 'sometimes|nullable|',
            'joiningDate'          => 'sometimes|nullable|date',
            'availabilityDuration' => 'sometimes|nullable|',
            'requiredAvailability' => 'sometimes|nullable|',
            'projectDuration'      => 'sometimes|nullable|',
            'status'               => 'sometimes|nullable|integer|in:1,2,3,4,5,6',
        ];
    }


}
