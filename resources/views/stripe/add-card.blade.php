<!DOCTYPE html>
<html>

<head>
  <title>Connect Card</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://js.stripe.com/v3/"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-md">
    <div class="flex justify-center mb-2">
      <div class="flex justify-center mb-2">
        <img src="https://www.vectorlogo.zone/logos/stripe/stripe-ar21.svg" alt="Stripe" class="h-18 md:h-18">
      </div>
    </div>
    <h2 class="text-2xl font-semibold text-center mb-6 text-gray-800">Add Card</h2>

    <form id="card-form" class="space-y-4">
      @csrf

      <div id="card-element" class="p-3 border border-gray-300 rounded-md bg-gray-50"></div>

      <button id="submit-button" type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out disabled:opacity-50">
        <svg id="loader" class="animate-spin h-5 w-5 mr-2 hidden" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span id="button-text">Submit</span>
      </button>
    </form>

    <p id="response" class="mt-4 text-sm text-center"></p>
  </div>

  <script>
    const stripe = Stripe('{{ $publishableKey }}', {
      stripeAccount: '{{ $connectAccountId }}'
    });

    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    const form = document.getElementById('card-form');
    const button = document.getElementById('submit-button');
    const loader = document.getElementById('loader');
    const buttonText = document.getElementById('button-text');
    const responseEl = document.getElementById('response');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Disable button and show loader
      button.disabled = true;
      loader.classList.remove('hidden');
      buttonText.textContent = 'Processing...';
      responseEl.textContent = '';

      const {
        token,
        error
      } = await stripe.createToken(card, {
        currency: '{{ $currency }}'
      });

      if (error) {
        responseEl.textContent = "❌ " + error.message;
        responseEl.className = "text-red-600";
        button.disabled = false;
        loader.classList.add('hidden');
        buttonText.textContent = 'Submit';
        return;
      }

      const res = await fetch('{{ route("save.card.token") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          _token: '{{ csrf_token() }}',
          token: token.id,
          sc_account_id: "{{ $connectAccountId }}"
        })
      });

      const text = await res.text();
      console.log("Server response:", text); // ✅ ADD THIS

      if (res.ok && text.startsWith("✅")) {
        responseEl.textContent = text;
        responseEl.className = "text-green-600";
        window.location.href = '{{ route("stripe.connect.card.success") }}'; // ✅ Only redirect on success
      } else {
        responseEl.textContent = text || '❌ Something went wrong.';
        responseEl.className = "text-red-600";
        button.disabled = false;
        loader.classList.add('hidden');
        buttonText.textContent = 'Submit';
      }
    });
  </script>
</body>

</html>