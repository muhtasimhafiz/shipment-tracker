<x-mail::message>
    # Shipment Status Update

    We regret to inform you that your shipment appears to be lost in transit.

    **Shipment Details:**
    - Tracking Number: {{ $shipment->tracking_number }}
    - Last Known Status: {{ $shipment->status }}
    - Last Updated: {{ $shipment->updated_at->format('F j, Y') }}

    Our team has been notified and will investigate this issue. We will contact you with updates as soon as possible.

    <x-mail::button :url="$shipment->tracking_url">
        Track Your Shipment
    </x-mail::button>

    If you have any questions, please don't hesitate to contact our support team.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
