{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
            <tr>
                <td class="bb-content bb-pb-0" align="center">
                    <table class="bb-icon bb-icon-lg bb-bg-orange" cellspacing="0" cellpadding="0">
                        <tbody>
                            <tr>
                                <td valign="middle" align="center">
                                    <img src="{{ 'shopping-cart' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Shopping Cart" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <h1 class="bb-text-center bb-m-0 bb-mt-md">Tu carrito te está esperando!</h1>
                    <p class="bb-text-center  bb-mt-sm bb-mt-0">No te pierdas estos fantásticos artículos.</p>
                </td>
            </tr>

            <tr>
                <td class="bb-content">
                    <p>Hola {{ customer_name }},</p>
                    <p>Hemos visto que añadiste artículos fantásticos a tu carrito, pero no completaste la compra. No te preocupes, ¡lo hemos guardado todo!</p>
                    <div align="center" style="margin-top: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                               style="border: 2px dashed #fe0000; border-radius: 8px; background: #fff5f5;">
                            <tbody>
                            <tr>
                                <td style="padding: 20px; text-align: center;">
                                    <p style="font-size: 16px; font-weight: 600; color: #fe0000; display: inline-flex; align-items: center; gap: 6px; margin:0;">
                                        <!-- Ícono de reloj -->
                                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"
                                             style="display: block; flex-shrink: 0;" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        ¡El tiempo se acaba!
                                    </p>
                                    <p style="margin: 12px 0 6px;">
                                        ¡Nuestro amable equipo de atención al cliente está aquí para ayudar!
                                    </p>
                                    <p style="margin: 6px 0 0;">
                                        Tus artículos están reservados, pero los más populares se agotan rápidamente. Completa tu compra ahora para evitar decepciones.
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                </td>
            </tr>

            <tr>
                <td class="bb-content bb-pt-0">
                    <h3 style="color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px;">📦 Items in your cart:</h3>
                    {{ product_list }}
                </td>
            </tr>


            <!-- Primary CTA -->
            <tr >
                <td class="bb-content" >
                    <table cellspacing="0" cellpadding="0" role="presentation">
                        <tbody>
                        <tr>
                            <td align="center">
                                <table cellpadding="0" cellspacing="0" border="0" style="background: #fe0000; border-radius: 6px;">
                                    <tr>
                                        <td align="center" valign="middle" style="padding: 14px 28px;">
                                            <a href="{{ site_url }}/checkout/{{ order_token }}/recover"  style="text-decoration: none; display: inline-block; font-weight: 600; font-size: 16px; color: #ffffff;">
                                                Completa tu compra
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
al
                </td>
            </tr>


        <tr>
            <td class="bb-content">
                <div style="margin-top: 25px; padding: 20px; background-color: #fff8e1; border: 1px solid #ffecb3; border-radius: 8px;">
                    <h4 style="color: #f57f17; margin-top: 0;">❓ Need Help?</h4>
                    <p style="color: #f57f17; margin-bottom: 10px;">Our friendly customer support team is here to help!</p>
                    <p style="color: #f57f17; margin-bottom: 0;">
                        📧 Email: <a href="mailto:{{ site_email }}" style="color: #f57f17;">{{ site_email }}</a><br>
                        📞 Phone: {% if store_phone and store_phone != '' %}{{ store_phone }}{% else %}No disponible{% endif %}
                    </p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
