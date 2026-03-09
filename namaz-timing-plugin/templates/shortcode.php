<div class="ntp-widget" data-default-city="<?php echo esc_attr($atts['city']); ?>" data-default-country="<?php echo esc_attr($atts['country']); ?>" data-default-method="<?php echo esc_attr($atts['method']); ?>">
    <div class="ntp-card ntp-input-card">
        <h3 class="ntp-title"><?php echo esc_html($atts['title']); ?></h3>
        <div class="ntp-grid">
            <label>
                <span><?php esc_html_e('City', 'namaz-timing-pro'); ?></span>
                <input type="text" class="ntp-city" placeholder="<?php esc_attr_e('e.g. Karachi', 'namaz-timing-pro'); ?>" />
            </label>
            <label>
                <span><?php esc_html_e('Country', 'namaz-timing-pro'); ?></span>
                <input type="text" class="ntp-country" placeholder="<?php esc_attr_e('e.g. Pakistan', 'namaz-timing-pro'); ?>" />
            </label>
            <label>
                <span><?php esc_html_e('Calculation Method', 'namaz-timing-pro'); ?></span>
                <select class="ntp-method">
                    <option value="1">University of Islamic Sciences, Karachi</option>
                    <option value="2" selected>Islamic Society of North America</option>
                    <option value="3">Muslim World League</option>
                    <option value="4">Umm Al-Qura University, Makkah</option>
                    <option value="5">Egyptian General Authority of Survey</option>
                    <option value="8">Gulf Region</option>
                    <option value="9">Kuwait</option>
                    <option value="10">Qatar</option>
                    <option value="11">Majlis Ugama Islam Singapura, Singapore</option>
                    <option value="13">Diyanet İşleri Başkanlığı, Turkey</option>
                </select>
            </label>
        </div>
        <button type="button" class="ntp-btn"></button>
    </div>

    <div class="ntp-card ntp-upcoming-card" hidden>
        <p class="ntp-meta-label"><?php esc_html_e('Upcoming Prayer', 'namaz-timing-pro'); ?></p>
        <h4 class="ntp-next-prayer"></h4>
        <p class="ntp-countdown"></p>
        <p class="ntp-location"></p>
    </div>

    <div class="ntp-card ntp-table-card" hidden>
        <h4 class="ntp-table-title"><?php esc_html_e('Today\'s Prayer Timings', 'namaz-timing-pro'); ?></h4>
        <table class="ntp-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Prayer', 'namaz-timing-pro'); ?></th>
                    <th><?php esc_html_e('Time', 'namaz-timing-pro'); ?></th>
                </tr>
            </thead>
            <tbody class="ntp-timings-body"></tbody>
        </table>
    </div>

    <p class="ntp-message" role="status" aria-live="polite"></p>
</div>
