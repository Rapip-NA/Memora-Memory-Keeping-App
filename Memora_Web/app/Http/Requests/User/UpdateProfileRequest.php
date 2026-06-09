<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name'           => ['sometimes', 'string', 'max:255'],
            'nickname'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'job'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'company'        => ['sometimes', 'nullable', 'string', 'max:150'],
            'bio'            => ['sometimes', 'nullable', 'string', 'max:500'],
            'quote'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'born_date'      => ['sometimes', 'nullable', 'date'],
            'lat'            => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'lng'            => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'social_links'   => ['sometimes', 'nullable', 'array'],
            'social_links.*' => ['nullable', 'url'],
            'classroom_id'   => ['sometimes', 'nullable', 'exists:classrooms,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string'          => 'Nama harus berupa teks.',
            'name.max'             => 'Nama maksimal 255 karakter.',

            'nickname.string'      => 'Nickname harus berupa teks.',
            'nickname.max'         => 'Nickname maksimal 100 karakter.',

            'city.string'          => 'Kota harus berupa teks.',
            'city.max'             => 'Kota maksimal 100 karakter.',

            'job.string'           => 'Pekerjaan harus berupa teks.',
            'job.max'              => 'Pekerjaan maksimal 100 karakter.',

            'company.string'       => 'Perusahaan harus berupa teks.',
            'company.max'          => 'Perusahaan maksimal 150 karakter.',

            'bio.string'           => 'Bio harus berupa teks.',
            'bio.max'              => 'Bio maksimal 500 karakter.',

            'quote.string'         => 'Quote harus berupa teks.',
            'quote.max'            => 'Quote maksimal 255 karakter.',

            'born_date.date'       => 'Format tanggal lahir tidak valid.',

            'lat.numeric'          => 'Latitude harus berupa angka.',
            'lat.between'          => 'Latitude harus antara -90 dan 90.',

            'lng.numeric'          => 'Longitude harus berupa angka.',
            'lng.between'          => 'Longitude harus antara -180 dan 180.',

            'social_links.array'   => 'Social links harus berupa array.',
            'social_links.*.url'   => 'Setiap social link harus berupa URL yang valid.',
            'classroom_id.exists'  => 'Kelas yang dipilih tidak valid.',
        ];
    }
}
