(function () {
  const widgets = document.querySelectorAll('.ntp-widget');

  widgets.forEach((widget) => {
    const cityInput = widget.querySelector('.ntp-city');
    const countryInput = widget.querySelector('.ntp-country');
    const methodInput = widget.querySelector('.ntp-method');
    const button = widget.querySelector('.ntp-btn');
    const message = widget.querySelector('.ntp-message');
    const resultCard = widget.querySelector('.ntp-result-card');
    const nextPrayerEl = widget.querySelector('.ntp-next-prayer');
    const countdownEl = widget.querySelector('.ntp-countdown');
    const locationEl = widget.querySelector('.ntp-location');
    const tbody = widget.querySelector('.ntp-timings-body');

    let countdownTimer = null;

    button.textContent = NTPData.i18n.button;

    cityInput.value = widget.dataset.defaultCity || NTPData.defaultCity || '';
    countryInput.value = widget.dataset.defaultCountry || NTPData.defaultCountry || '';
    methodInput.value = widget.dataset.defaultMethod || NTPData.defaultMethod || '2';

    function setMessage(text, isError = false) {
      message.textContent = text;
      message.classList.toggle('error', isError);
    }

    function formatCountdown(secondsLeft) {
      const hours = Math.floor(secondsLeft / 3600);
      const minutes = Math.floor((secondsLeft % 3600) / 60);
      const seconds = secondsLeft % 60;
      return `${hours}h ${minutes}m ${seconds}s`;
    }

    function startCountdown(timestamp) {
      if (countdownTimer) {
        clearInterval(countdownTimer);
      }

      const tick = () => {
        const now = Math.floor(Date.now() / 1000);
        const left = Math.max(timestamp - now, 0);
        countdownEl.textContent = formatCountdown(left);
      };

      tick();
      countdownTimer = setInterval(tick, 1000);
    }

    function renderTimings(timings) {
      tbody.innerHTML = '';
      Object.entries(timings).forEach(([name, time]) => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${name}</td><td>${time}</td>`;
        tbody.appendChild(row);
      });
    }

    async function fetchPrayerTimes() {
      const city = cityInput.value.trim();
      const country = countryInput.value.trim();
      const method = methodInput.value;

      if (!city || !country) {
        setMessage('City and country are required.', true);
        return;
      }

      setMessage(NTPData.i18n.loading);
      button.disabled = true;

      const formData = new FormData();
      formData.append('action', 'ntp_get_prayer_times');
      formData.append('nonce', NTPData.nonce);
      formData.append('city', city);
      formData.append('country', country);
      formData.append('method', method);

      try {
        const response = await fetch(NTPData.ajaxUrl, {
          method: 'POST',
          body: formData,
        });

        const payload = await response.json();

        if (!payload.success) {
          throw new Error(payload?.data?.message || NTPData.i18n.error);
        }

        const { timings, meta, nextPrayer } = payload.data;
        resultCard.hidden = false;
        nextPrayerEl.textContent = `${nextPrayer.name} - ${nextPrayer.time}`;
        locationEl.textContent = `${NTPData.i18n.location}: ${meta.location} (${meta.date})`;
        renderTimings(timings);
        startCountdown(nextPrayer.timestamp);
        setMessage('');
      } catch (error) {
        setMessage(error.message || NTPData.i18n.error, true);
      } finally {
        button.disabled = false;
      }
    }

    button.addEventListener('click', fetchPrayerTimes);
  });
})();
