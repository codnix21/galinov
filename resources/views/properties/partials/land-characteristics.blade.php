@php
    $landRows = \App\Support\PropertyLandAttributes::displayRows($property);
@endphp
@if(count($landRows) > 0)
    <div class="col-span-2 mt-2 pt-4 border-t border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Параметры участка</h3>
        <div class="grid grid-cols-2 gap-6">
            @foreach($landRows as $row)
                <div class="pb-4 border-b border-gray-200">
                    <span class="text-sm text-gray-600 block mb-1">{{ $row['label'] }}</span>
                    <span class="font-medium text-lg">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
