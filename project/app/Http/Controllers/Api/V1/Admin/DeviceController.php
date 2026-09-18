<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends CrudController
{
    protected string $model = Device::class;

    protected string $resource = DeviceResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'platform' => ['sometimes', Rule::in(Device::PLATFORMS)],
            'app_version' => ['nullable', 'string', 'max:30'],
            'notifications_enabled' => ['boolean'],
        ];
    }
}
