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
            'content'  => 'required|string|max:2000',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'category' => 'required|in:karier,pendidikan,keluarga,perjalanan,lainnya',
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

            'photo.image'      => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'      => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max'        => 'Ukuran foto tidak boleh lebih dari 2 MB.',

            'category.required' => 'Kategori post tidak boleh kosong.',
            'category.in'       => 'Kategori tidak valid. Pilih salah satu: karier, pendidikan, keluarga, perjalanan, lainnya.',
        ];
    }
}
