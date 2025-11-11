/**
 * Exchange Form Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const sendCurrencyWrapper = document.getElementById('send-currency-wrapper');
    const receiveCurrencyWrapper = document.getElementById('receive-currency-wrapper');
    const sendAmountInput = document.getElementById('send-amount');
    const receiveAmountInput = document.getElementById('receive-amount');
    const exchangeRateDisplay = document.getElementById('exchange-rate-display');
    const swapCurrenciesBtn = document.getElementById('swap-currencies');
    const continueToStep2Btn = document.getElementById('continue-to-step-2');
    const backToStep1Btn = document.getElementById('back-to-step-1');
    const continueToStep3Btn = document.getElementById('continue-to-step-3');
    const backToStep2Btn = document.getElementById('back-to-step-2');
    const submitExchangeBtn = document.getElementById('submit-exchange');
    
    const step1Exchange = document.getElementById('step-1-exchange');
    const step2Contact = document.getElementById('step-2-contact');
    const step3Confirmation = document.getElementById('step-3-confirmation');
    
    // Exchange rate data (will be populated from backend)
    let exchangeRates = {};
    let currencies = {};
    
    // Current selected currencies
    let selectedSendCurrency = '';
    let selectedReceiveCurrency = '';
    
    // Initialize custom select dropdowns
    initializeCustomSelects();
    
    // Load exchange rates
    loadExchangeRates();
    
    // Handle send amount input
    sendAmountInput.addEventListener('input', updateReceiveAmount);
    
    // Handle swap currencies button
    if (swapCurrenciesBtn) {
        swapCurrenciesBtn.addEventListener('click', swapCurrencies);
    }
    
    // Handle step navigation
    if (continueToStep2Btn) {
        continueToStep2Btn.addEventListener('click', goToStep2);
    }
    
    if (backToStep1Btn) {
        backToStep1Btn.addEventListener('click', goToStep1);
    }
    
    if (continueToStep3Btn) {
        continueToStep3Btn.addEventListener('click', goToStep3);
    }
    
    if (backToStep2Btn) {
        backToStep2Btn.addEventListener('click', goToStep2);
    }
    
    if (submitExchangeBtn) {
        submitExchangeBtn.addEventListener('click', submitExchange);
    }
    
    /**
     * Initialize custom select dropdowns
     */
    function initializeCustomSelects() {
        // Find all custom select elements
        const customSelects = document.querySelectorAll('.custom-select');
        
        customSelects.forEach(select => {
            const trigger = select.querySelector('.custom-select__trigger');
            const options = select.querySelector('.custom-options');
            
            // Toggle dropdown when trigger is clicked
            trigger.addEventListener('click', () => {
                select.classList.toggle('open');
            });
            
            // Handle option selection
            if (options) {
                const optionElements = options.querySelectorAll('.custom-option');
                
                optionElements.forEach(option => {
                    option.addEventListener('click', () => {
                        // Get selected value
                        const value = option.getAttribute('data-value');
                        
                        // Update selected display
                        const selectedDisplay = select.querySelector('.custom-select__selected-display');
                        selectedDisplay.innerHTML = option.innerHTML;
                        
                        // Update selected value
                        const selectedValue = select.querySelector('.custom-select__selected-value');
                        if (selectedValue) {
                            selectedValue.textContent = option.textContent;
                        }
                        
                        // Update currency selection
                        if (select.closest('#send-currency-wrapper')) {
                            selectedSendCurrency = value;
                        } else if (select.closest('#receive-currency-wrapper')) {
                            selectedReceiveCurrency = value;
                        }
                        
                        // Update exchange rate display
                        updateExchangeRateDisplay();
                        
                        // Update receive amount
                        updateReceiveAmount();
                        
                        // Close dropdown
                        select.classList.remove('open');
                    });
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', event => {
                if (!select.contains(event.target)) {
                    select.classList.remove('open');
                }
            });
        });
        
        // Get initial selected currencies
        const sendSelected = document.querySelector('#send-currency-wrapper .custom-option[data-value]');
        const receiveSelected = document.querySelector('#receive-currency-wrapper .custom-option[data-value]');
        
        if (sendSelected) {
            selectedSendCurrency = sendSelected.getAttribute('data-value');
        }
        
        if (receiveSelected) {
            selectedReceiveCurrency = receiveSelected.getAttribute('data-value');
        }
    }
    
    /**
     * Load exchange rates from server
     */
    function loadExchangeRates() {
        // Using fetch API to get exchange rates
        fetch('api/get_rates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exchangeRates = data.rates;
                    currencies = data.currencies;
                    
                    // Update exchange rate display
                    updateExchangeRateDisplay();
                    
                    // Update receive amount based on current input
                    updateReceiveAmount();
                } else {
                    console.error('Failed to load exchange rates:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading exchange rates:', error);
            });
    }
    
    /**
     * Update exchange rate display
     */
    function updateExchangeRateDisplay() {
        if (!selectedSendCurrency || !selectedReceiveCurrency) {
            return;
        }
        
        const rateKey = `${selectedSendCurrency}_${selectedReceiveCurrency}`;
        const rate = exchangeRates[rateKey];
        
        if (rate) {
            // Get currency display names
            const fromCurrency = currencies[selectedSendCurrency] || selectedSendCurrency;
            const toCurrency = currencies[selectedReceiveCurrency] || selectedReceiveCurrency;
            
            // Update exchange rate display
            exchangeRateDisplay.innerHTML = `<i class="fas fa-chart-line mr-2"></i> Exchange Rate: 1 ${fromCurrency} = ${rate.toFixed(4)} ${toCurrency}`;
        } else {
            exchangeRateDisplay.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> Exchange rate not available`;
        }
    }
    
    /**
     * Update receive amount based on send amount
     */
    function updateReceiveAmount() {
        if (!selectedSendCurrency || !selectedReceiveCurrency) {
            return;
        }
        
        const sendAmount = parseFloat(sendAmountInput.value) || 0;
        const rateKey = `${selectedSendCurrency}_${selectedReceiveCurrency}`;
        const rate = exchangeRates[rateKey];
        
        if (rate) {
            const receiveAmount = sendAmount * rate;
            receiveAmountInput.value = receiveAmount.toFixed(2);
        } else {
            receiveAmountInput.value = '0.00';
        }
    }
    
    /**
     * Swap currencies
     */
    function swapCurrencies() {
        // Swap selected currencies
        const tempCurrency = selectedSendCurrency;
        selectedSendCurrency = selectedReceiveCurrency;
        selectedReceiveCurrency = tempCurrency;
        
        // Update UI for send currency
        const sendOptions = document.querySelectorAll('#send-currency-wrapper .custom-option');
        sendOptions.forEach(option => {
            if (option.getAttribute('data-value') === selectedSendCurrency) {
                const sendDisplay = document.querySelector('#send-currency-wrapper .custom-select__selected-display');
                sendDisplay.innerHTML = option.innerHTML;
            }
        });
        
        // Update UI for receive currency
        const receiveOptions = document.querySelectorAll('#receive-currency-wrapper .custom-option');
        receiveOptions.forEach(option => {
            if (option.getAttribute('data-value') === selectedReceiveCurrency) {
                const receiveDisplay = document.querySelector('#receive-currency-wrapper .custom-select__selected-display');
                receiveDisplay.innerHTML = option.innerHTML;
            }
        });
        
        // Swap amounts
        const tempAmount = sendAmountInput.value;
        sendAmountInput.value = receiveAmountInput.value;
        receiveAmountInput.value = tempAmount;
        
        // Update exchange rate display
        updateExchangeRateDisplay();
    }
    
    /**
     * Go to step 2 (contact information)
     */
    function goToStep2() {
        // Validate step 1
        if (!validateStep1()) {
            return;
        }
        
        // Hide step 1, show step 2
        step1Exchange.classList.add('hidden');
        step2Contact.classList.remove('hidden');
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    
    /**
     * Go back to step 1
     */
    function goToStep1() {
        // Hide step 2, show step 1
        step2Contact.classList.add('hidden');
        step1Exchange.classList.remove('hidden');
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    
    /**
     * Go to step 3 (confirmation)
     */
    function goToStep3() {
        // Validate step 2
        if (!validateStep2()) {
            return;
        }
        
        // Update confirmation page with form data
        updateConfirmationPage();
        
        // Hide step 2, show step 3
        step2Contact.classList.add('hidden');
        step3Confirmation.classList.remove('hidden');
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    
    /**
     * Go back to step 2
     */
    function goToStep2() {
        // Hide step 3, show step 2
        step3Confirmation.classList.add('hidden');
        step2Contact.classList.remove('hidden');
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    
    /**
     * Validate step 1 (exchange details)
     */
    function validateStep1() {
        // Check if currencies are selected
        if (!selectedSendCurrency) {
            showToast('Error', 'Please select the currency you want to send', 'error');
            return false;
        }
        
        if (!selectedReceiveCurrency) {
            showToast('Error', 'Please select the currency you want to receive', 'error');
            return false;
        }
        
        // Check if send amount is valid
        const sendAmount = parseFloat(sendAmountInput.value);
        if (isNaN(sendAmount) || sendAmount <= 0) {
            showToast('Error', 'Please enter a valid amount to send', 'error');
            sendAmountInput.focus();
            return false;
        }
        
        // Check if exchange rate is available
        const rateKey = `${selectedSendCurrency}_${selectedReceiveCurrency}`;
        if (!exchangeRates[rateKey]) {
            showToast('Error', 'Exchange rate not available for the selected currencies', 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate step 2 (contact information)
     */
    function validateStep2() {
        // Get form elements
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const paymentAddressInput = document.getElementById('payment-address');
        
        // Validate name
        if (!nameInput.value.trim()) {
            showToast('Error', 'Please enter your name', 'error');
            nameInput.focus();
            return false;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value.trim() || !emailRegex.test(emailInput.value.trim())) {
            showToast('Error', 'Please enter a valid email address', 'error');
            emailInput.focus();
            return false;
        }
        
        // Validate phone
        if (!phoneInput.value.trim()) {
            showToast('Error', 'Please enter your phone number', 'error');
            phoneInput.focus();
            return false;
        }
        
        // Validate payment address
        if (!paymentAddressInput.value.trim()) {
            showToast('Error', 'Please enter your payment address', 'error');
            paymentAddressInput.focus();
            return false;
        }
        
        return true;
    }
    
    /**
     * Update confirmation page with form data
     */
    function updateConfirmationPage() {
        // Generate reference ID
        const referenceId = generateReferenceId();
        document.getElementById('reference-id').textContent = referenceId;
        document.getElementById('hidden-reference-id').value = referenceId;
        
        // Update customer info
        document.getElementById('confirm-name').textContent = document.getElementById('name').value;
        document.getElementById('confirm-email').textContent = document.getElementById('email').value;
        document.getElementById('confirm-phone').textContent = document.getElementById('phone').value;
        
        // Update exchange info
        const sendCurrencyName = document.querySelector('#send-currency-wrapper .custom-select__selected-value').textContent;
        const receiveCurrencyName = document.querySelector('#receive-currency-wrapper .custom-select__selected-value').textContent;
        
        document.getElementById('confirm-send-currency').textContent = sendCurrencyName;
        document.getElementById('confirm-receive-currency').textContent = receiveCurrencyName;
        document.getElementById('confirm-send-amount').textContent = parseFloat(sendAmountInput.value).toFixed(2);
        document.getElementById('confirm-receive-amount').textContent = parseFloat(receiveAmountInput.value).toFixed(2);
        
        // Update payment info
        const paymentAddressType = document.getElementById('payment-address-type').textContent;
        document.getElementById('confirm-payment-address-type').textContent = paymentAddressType;
        document.getElementById('confirm-payment-address').textContent = document.getElementById('payment-address').value;
        
        // Update currency send instructions (replace with real data in production)
        const paymentInstruction = document.getElementById('payment-instruction');
        paymentInstruction.innerHTML = `Please send <strong>${parseFloat(sendAmountInput.value).toFixed(2)} ${sendCurrencyName}</strong> to the following address:`;
        
        // For demonstration purposes, show a placeholder payment address
        // In production, this should come from your server/database
        document.getElementById('payment-address-display').textContent = '01869838872 (bKash)';
    }
    
    /**
     * Generate a unique reference ID
     */
    function generateReferenceId() {
        const prefix = 'EB-';
        const timestamp = new Date().toISOString().slice(2, 10).replace(/-/g, '');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return prefix + timestamp + random;
    }
    
    /**
     * Submit the exchange
     */
    function submitExchange() {
        // Get all form data
        const formData = new FormData();
        
        // Add reference ID
        formData.append('reference_id', document.getElementById('hidden-reference-id').value);
        
        // Add customer info
        formData.append('customer_name', document.getElementById('name').value);
        formData.append('customer_email', document.getElementById('email').value);
        formData.append('customer_phone', document.getElementById('phone').value);
        formData.append('payment_address', document.getElementById('payment-address').value);
        
        // Add exchange info
        formData.append('from_currency', selectedSendCurrency);
        formData.append('to_currency', selectedReceiveCurrency);
        formData.append('send_amount', sendAmountInput.value);
        formData.append('receive_amount', receiveAmountInput.value);
        
        // Show loading state
        submitExchangeBtn.disabled = true;
        submitExchangeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
        
        // Submit to server
        fetch('api/exchange.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('Success', 'Your exchange has been submitted successfully!', 'success');
                
                // Redirect to receipt page
                setTimeout(() => {
                    window.location.href = 'receipt.php?ref=' + document.getElementById('hidden-reference-id').value;
                }, 2000);
            } else {
                // Show error message
                showToast('Error', data.message || 'An error occurred while processing your exchange', 'error');
                
                // Reset submit button
                submitExchangeBtn.disabled = false;
                submitExchangeBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm Exchange';
            }
        })
        .catch(error => {
            console.error('Error submitting exchange:', error);
            
            // Show error message
            showToast('Error', 'An error occurred while processing your exchange', 'error');
            
            // Reset submit button
            submitExchangeBtn.disabled = false;
            submitExchangeBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm Exchange';
        });
    }
});