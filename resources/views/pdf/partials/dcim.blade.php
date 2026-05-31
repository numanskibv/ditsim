<h2>DCIM-stand</h2>
@foreach ($racks as $rack)
    <p style="margin-bottom:2px;"><strong>{{ $rack->name }}</strong> — {{ $rack->location }} ({{ $rack->height_u }}U)</p>
    <table>
        <tr><th>Device</th><th>Type</th><th>U</th><th>Status</th></tr>
        @foreach ($rack->devices as $device)
            <tr>
                <td>{{ $device->name }}</td>
                <td>{{ $device->type->label() }}</td>
                <td>U{{ $device->u_start }}–{{ $device->u_end }}</td>
                <td><span class="badge {{ $device->status->value }}">{{ $device->status->label() }}</span></td>
            </tr>
        @endforeach
    </table>
@endforeach
