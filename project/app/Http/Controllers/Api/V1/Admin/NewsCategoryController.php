<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\NewsCategory;

class NewsCategoryController extends CategoryController
{
    protected string $model = NewsCategory::class;
}
