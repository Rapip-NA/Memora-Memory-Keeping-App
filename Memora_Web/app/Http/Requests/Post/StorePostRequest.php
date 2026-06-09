<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'content'            => 'required|string|max:2000',
            'photo'              => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi,webm,mkv,3gp|max:102400',
            'category'           => 'required|in:karier,pendidikan,keluarga,perjalanan,lainnya',
            'poll_options'       => 'nullable|array|min:2',
            'poll_options.*'     => 'required_with:poll_options|string|max:100',
            'poll_duration_days' => 'nullable|integer|min:1|max:30',
        ];
    }

    /**
     * Get custom validation messages in Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Isi konten post tidak boleh kosong.',
            'content.string'   => 'Konten post harus berupa teks.',
            'content.max'      => 'Konten post tidak boleh lebih dari 2000 karakter.',

            'photo.file'       => 'Unggahan harus berupa file.',
            'photo.mimes'      => 'Format file harus gambar (jpg, jpeg, png) atau video (mp4, mov, avi, webm, mkv, 3gp).',
            'photo.max'        => 'Ukuran file tidak boleh lebih dari 100 MB.',

            'category.required' => 'Kategori post tidak boleh kosong.',
            'category.in'       => 'Kategori tidak valid. Pilih salah satu: karier, pendidikan, keluarga, perjalanan, lainnya.',
        ];
    }
}
