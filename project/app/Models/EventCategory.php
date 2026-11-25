<?php

namespace App\Models;

use App\Models\Concerns\IsCategory;
use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property int $display_order
 * @property bool $is_active
 */
class EventCategory extends Model
{
    use HasUuids, IsCategory, RefreshesPublicCache;
}
