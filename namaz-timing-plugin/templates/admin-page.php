<div class="wrap ntp-admin-wrap">
    <h1><?php esc_html_e('Namaz Timing Pro Dashboard', 'namaz-timing-pro'); ?></h1>

    <div class="ntp-admin-tabs">
        <button class="nav-tab nav-tab-active" data-tab="stats"><?php esc_html_e('Stats', 'namaz-timing-pro'); ?></button>
        <button class="nav-tab" data-tab="shortcodes"><?php esc_html_e('Shortcode Builder', 'namaz-timing-pro'); ?></button>
    </div>

    <section class="ntp-panel is-active" id="ntp-tab-stats">
        <div class="ntp-kpi-grid">
            <article class="ntp-kpi-card">
                <h3><?php esc_html_e('Total API Requests', 'namaz-timing-pro'); ?></h3>
                <p><?php echo esc_html((string) ($stats['total_requests'] ?? 0)); ?></p>
            </article>
            <article class="ntp-kpi-card">
                <h3><?php esc_html_e('Saved Presets', 'namaz-timing-pro'); ?></h3>
                <p><?php echo esc_html((string) count($presets)); ?></p>
            </article>
        </div>

        <div class="ntp-card">
            <h3><?php esc_html_e('Top Locations', 'namaz-timing-pro'); ?></h3>
            <ol>
                <?php if (!empty($stats['top_locations'])) : ?>
                    <?php foreach ($stats['top_locations'] as $location => $count) : ?>
                        <li>
                            <strong><?php echo esc_html(ucwords($location)); ?></strong>
                            <span><?php echo esc_html((string) $count); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <li><?php esc_html_e('No usage data yet.', 'namaz-timing-pro'); ?></li>
                <?php endif; ?>
            </ol>
        </div>
    </section>

    <section class="ntp-panel" id="ntp-tab-shortcodes">
        <div class="ntp-card">
            <h3><?php esc_html_e('Create Default City Shortcode', 'namaz-timing-pro'); ?></h3>
            <form id="ntp-preset-form">
                <div class="ntp-form-grid">
                    <label>
                        <span><?php esc_html_e('Label', 'namaz-timing-pro'); ?></span>
                        <input type="text" name="label" required />
                    </label>
                    <label>
                        <span><?php esc_html_e('City', 'namaz-timing-pro'); ?></span>
                        <input type="text" name="city" required />
                    </label>
                    <label>
                        <span><?php esc_html_e('Country', 'namaz-timing-pro'); ?></span>
                        <input type="text" name="country" required />
                    </label>
                    <label>
                        <span><?php esc_html_e('Method', 'namaz-timing-pro'); ?></span>
                        <input type="number" name="method" value="2" min="1" max="99" required />
                    </label>
                </div>
                <button class="button button-primary" type="submit"><?php esc_html_e('Save Preset', 'namaz-timing-pro'); ?></button>
            </form>
            <p class="ntp-admin-message"></p>
        </div>

        <div class="ntp-card">
            <h3><?php esc_html_e('Saved Shortcodes', 'namaz-timing-pro'); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Label', 'namaz-timing-pro'); ?></th>
                        <th><?php esc_html_e('City', 'namaz-timing-pro'); ?></th>
                        <th><?php esc_html_e('Country', 'namaz-timing-pro'); ?></th>
                        <th><?php esc_html_e('Method', 'namaz-timing-pro'); ?></th>
                        <th><?php esc_html_e('Shortcode', 'namaz-timing-pro'); ?></th>
                        <th><?php esc_html_e('Action', 'namaz-timing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody id="ntp-presets-body">
                    <?php if (!empty($presets)) : ?>
                        <?php foreach ($presets as $id => $preset) : ?>
                            <tr data-id="<?php echo esc_attr($id); ?>">
                                <td><?php echo esc_html($preset['label']); ?></td>
                                <td><?php echo esc_html($preset['city']); ?></td>
                                <td><?php echo esc_html($preset['country']); ?></td>
                                <td><?php echo esc_html((string) $preset['method']); ?></td>
                                <td><code><?php echo esc_html($preset['shortcode']); ?></code></td>
                                <td><button class="button-link-delete ntp-delete-preset" type="button"><?php esc_html_e('Delete', 'namaz-timing-pro'); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="6"><?php esc_html_e('No presets created yet.', 'namaz-timing-pro'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
