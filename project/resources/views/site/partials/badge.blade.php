@php($scope = \App\Support\SiteFormat::scope($item))
<span class="badge badge-{{ $scope['tone'] }}">{{ $scope['label'] }}</span>
