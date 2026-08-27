@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <h1 class="h3 mb-1">Checkout</h1>

    @guest
        <p class="text-muted mb-4">
            Checking out as a guest.
            <a href="{{ route('login') }}">Log in</a> or <a href="{{ route('register') }}">create an account</a>
            to keep track of your orders.
        </p>
    @else
        <div class="mb-4"></div>
    @endguest

    <div class="row g-4">
        <div class="col-lg-7">
            <form action="{{ route('checkout.store') }}" method="POST">
                @csrf

                <div class="card mb-4">
                    <div class="card-header">Contact details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="contact_name">Full name</label>
                            <input type="text" name="contact_name" id="contact_name" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name', auth()->user()?->name) }}" required>
                            @error('contact_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="contact_email">Email</label>
                            <input type="email" name="contact_email" id="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', auth()->user()?->email) }}" required>
                            @error('contact_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Shipping address</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="shipping_address_line1">Address line 1</label>
                            <input type="text" name="shipping_address_line1" id="shipping_address_line1" class="form-control @error('shipping_address_line1') is-invalid @enderror" value="{{ old('shipping_address_line1') }}" required>
                            @error('shipping_address_line1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="shipping_address_line2">Address line 2 <span class="text-muted">(optional)</span></label>
                            <input type="text" name="shipping_address_line2" id="shipping_address_line2" class="form-control @error('shipping_address_line2') is-invalid @enderror" value="{{ old('shipping_address_line2') }}">
                            @error('shipping_address_line2')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="shipping_city">City</label>
                                <input type="text" name="shipping_city" id="shipping_city" class="form-control @error('shipping_city') is-invalid @enderror" value="{{ old('shipping_city') }}" required>
                                @error('shipping_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="shipping_postcode">Postcode</label>
                                <input type="text" name="shipping_postcode" id="shipping_postcode" class="form-control @error('shipping_postcode') is-invalid @enderror" value="{{ old('shipping_postcode') }}" required>
                                @error('shipping_postcode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="shipping_country">Country</label>
                            <input type="text" name="shipping_country" id="shipping_country" class="form-control @error('shipping_country') is-invalid @enderror" value="{{ old('shipping_country', 'United Kingdom') }}" required>
                            @error('shipping_country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Place order</button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="sticky-lg-top" style="top: 5rem;">
                <div class="card">
                    <div class="card-header">Order summary</div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            {{ $item['name'] }}
                                            @if ($item['options_label'])
                                                <div class="text-muted small">{{ $item['options_label'] }}</div>
                                            @endif
                                            <div class="text-muted small">Qty: {{ $item['quantity'] }}</div>
                                        </td>
                                        <td class="text-end">£{{ number_format($item['unit_price'] * $item['quantity'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-between fs-5 fw-semibold">
                        <span>Subtotal</span>
                        <span>£{{ number_format($subtotal, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
