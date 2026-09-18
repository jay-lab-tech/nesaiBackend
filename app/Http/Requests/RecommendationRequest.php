<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RecommendationRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['interests' => ['required','array','min:1'], 'interests.*' => ['string','max:100']]; } }
