<?php
namespace App\Services;
use App\Models\Content; use App\Models\Major; use App\Models\News;
class SearchService { public function search(string $query): array { $like = '%'.str_replace(['%','_'], ['\\%','\\_'], $query).'%'; return ['majors' => Major::query()->where('name','ilike',$like)->get(), 'news' => News::query()->where('title','ilike',$like)->get(), 'content' => Content::query()->where('is_published',true)->where('title','ilike',$like)->get()]; } }
