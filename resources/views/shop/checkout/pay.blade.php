@extends('layouts.app')

@section('title', 'Pay for order '.$order->order_number)

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="h3 mb-1">Pay for your order</h1>
            <p class="text-muted mb-4">Order {{ $order->order_number }} — £{{ number_format($order->total, 2) }}</p>

            <div id="sumup-payment-error" class="alert alert-danger d-none"></div>

            <div id="sumup-card"></div>
        </div>
    </div>

    @push('scripts')
        <script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>
        <script>
            (function () {
                const errorBox = document.getElementById('sumup-payment-error');

                const showError = (message) => {
                    errorBox.textContent = message;
                    errorBox.classList.remove('d-none');
                };

                SumUpCard.mount({
                    id: 'sumup-card',
                    checkoutId: @json($order->sumup_checkout_id),
                    onResponse: function (type, body) {
                        // "success" here only means the widget's own request came
                        // back ok — SumUp's own docs note that alone doesn't mean
                        // the payment succeeded. checkout.return below is just a
                        // holding page either way; the webhook (server-to-server,
                        // re-verified against SumUp directly) is what actually
                        // marks the order paid.
                        if (type === 'success') {
                            window.location.href = @json(route('checkout.return', $order));
                            return;
                        }

                        if (type === 'error' || type === 'fail') {
                            showError((body && body.message) || 'Payment failed — please check your card details and try again.');
                            return;
                        }

                        if (type === 'invalid') {
                            showError('Please check your card details and try again.');
                        }

                        // "sent" and "auth-screen" are just in-progress states
                        // (card submitted, 3D Secure challenge shown) — nothing
                        // for this page to do for those.
                    },
                });
            })();
        </script>
    @endpush
@endsection
