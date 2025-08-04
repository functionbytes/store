{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'shopping-cart' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">Don't let these items slip away!</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p>Hi {{ customer_name }},</p>
                <div>You left some amazing items in your cart! We've saved them for you, but they won't last forever. Complete your purchase now to secure these great products.</div>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-text-center bb-pt-0 bb-pb-xl">
                <table cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td align="center">
                            <table cellpadding="0" cellspacing="0" border="0" class="bb-bg-blue bb-rounded bb-w-auto">
                                <tbody>
                                    <tr>
                                        <td align="center" valign="top" class="lh-1">
                                            <a href="{{ site_url }}/checkout/{{ order_token }}/recover" class="bb-btn bb-bg-blue bb-border-blue">
                                                <span class="btn-span">Complete Your Purchase</span>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <h4>Your reserved items:</h4>
                {{ order_recover }}

                {% if order_note %}
                <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
                    <strong>Your note:</strong> {{ order_note }}
                </div>
                {% endif %}
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <div style="margin-top: 30px; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">
                    <h4 style="color: #856404; margin-top: 0;">⏰ Limited Time Offer</h4>
                    <p style="color: #856404; margin-bottom: 0;">Complete your purchase in the next 24 hours and get <strong>free shipping</strong> on your order!</p>
                </div>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p style="margin-top: 30px;">Need help? Feel free to reach out to our customer support team.</p>
                <p style="margin-bottom: 0;">Happy shopping!</p>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
