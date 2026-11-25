<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\EventCategory;

class EventCategoryController extends CategoryController
{
    protected string $model = EventCategory::class;
}
