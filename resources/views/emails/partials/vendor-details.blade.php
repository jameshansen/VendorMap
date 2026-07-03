<table cellpadding="6" style="border-collapse:collapse;">
    <tr><td><strong>Business</strong></td><td>{{ $vendor->business_name }}</td></tr>
    <tr><td><strong>Contact</strong></td><td>{{ $vendor->contact_name }}</td></tr>
    <tr><td><strong>Email</strong></td><td>{{ $vendor->email }}</td></tr>
    @if ($vendor->phone)
        <tr><td><strong>Phone</strong></td><td>{{ $vendor->phone }}</td></tr>
    @endif
    @if ($vendor->address)
        <tr><td valign="top"><strong>Address</strong></td><td>{{ $vendor->address }}</td></tr>
    @endif
    @if ($vendor->website)
        <tr><td><strong>Website</strong></td><td>{{ $vendor->website }}</td></tr>
    @endif
    @if (! empty($vendor->categories))
        <tr><td valign="top"><strong>Products</strong></td><td>{{ implode(', ', $vendor->categories) }}</td></tr>
    @endif
</table>
