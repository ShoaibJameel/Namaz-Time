(function ($) {
  const tabs = $('.ntp-admin-tabs .nav-tab');
  const panels = $('.ntp-panel');

  tabs.on('click', function () {
    const tab = $(this).data('tab');
    tabs.removeClass('nav-tab-active');
    $(this).addClass('nav-tab-active');
    panels.removeClass('is-active');
    $(`#ntp-tab-${tab}`).addClass('is-active');
  });

  const form = $('#ntp-preset-form');
  const body = $('#ntp-presets-body');
  const msg = $('.ntp-admin-message');

  function showMsg(text, isError = false) {
    msg.text(text).toggleClass('error', isError);
  }

  form.on('submit', function (event) {
    event.preventDefault();

    const data = form.serializeArray();
    data.push({ name: 'action', value: 'ntp_save_preset' });
    data.push({ name: 'nonce', value: NTPAdmin.nonce });

    $.post(NTPAdmin.ajaxUrl, $.param(data))
      .done(function (response) {
        if (!response.success) {
          showMsg(response?.data?.message || NTPAdmin.messages.error, true);
          return;
        }

        const preset = response.data.preset;
        const id = response.data.id;
        const row = `<tr data-id="${id}">
          <td>${preset.label}</td>
          <td>${preset.city}</td>
          <td>${preset.country}</td>
          <td>${preset.method}</td>
          <td><code>${preset.shortcode}</code></td>
          <td><button class="button-link-delete ntp-delete-preset" type="button">Delete</button></td>
        </tr>`;

        if (body.find('td[colspan="6"]').length) {
          body.html(row);
        } else {
          body.append(row);
        }

        showMsg(NTPAdmin.messages.saved);
        form.trigger('reset');
      })
      .fail(function () {
        showMsg(NTPAdmin.messages.error, true);
      });
  });

  body.on('click', '.ntp-delete-preset', function () {
    const row = $(this).closest('tr');
    const id = row.data('id');

    if (!id) {
      row.remove();
      return;
    }

    $.post(NTPAdmin.ajaxUrl, {
      action: 'ntp_delete_preset',
      nonce: NTPAdmin.nonce,
      id,
    })
      .done(function (response) {
        if (!response.success) {
          showMsg(response?.data?.message || NTPAdmin.messages.error, true);
          return;
        }
        row.remove();
        showMsg(NTPAdmin.messages.deleted);
      })
      .fail(function () {
        showMsg(NTPAdmin.messages.error, true);
      });
  });
})(jQuery);
