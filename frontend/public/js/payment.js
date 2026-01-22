document.addEventListener('DOMContentLoaded', () => {
    const payBtn = document.getElementById('pay-button');

    if (payBtn) {
        payBtn.addEventListener('click', async () => {
            // retreive data
            const orderId = payBtn.getAttribute('data-order-id');
            const amount = payBtn.getAttribute('data-amount');

            payBtn.innerText = "Processing...";
            payBtn.disabled = true;

            try {
                // call backend
                const response = await fetch('http://localhost/HFABS/backend/public/index.php?url=payment/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        amount: amount
                    })
                });

                const result = await response.json();

                // redirect  to paymongo
                if (result.checkout_url) {
                    window.location.href = result.checkout_url;
                } else {
                    alert("Error: Could not get payment link.");
                    payBtn.disabled = false;
                    payBtn.innerText = "Pay";
                }
            } catch (error) {
                console.error("Payment Error:", error);
                alert("Technical error occurred.");
                payBtn.disabled = false;
            }
        });
    }
});