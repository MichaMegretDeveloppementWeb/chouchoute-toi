<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #F9F6F2; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F9F6F2; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #512731; padding: 30px 40px; border-radius: 12px 12px 0 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: 3px; text-transform: uppercase;">
                                Chouchoute-toi
                            </h1>
                            <p style="margin: 6px 0 0; font-size: 13px; color: rgba(255,255,255,0.7);">
                                Nouvelle demande de contact
                            </p>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">

                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #333333;">
                                Une nouvelle demande a été envoyée depuis le formulaire de contact du site.
                            </p>

                            {{-- Infos client --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #F9F6F2; border-radius: 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.06);">
                                                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Nom</span><br>
                                                    <span style="font-size: 15px; color: #222222; font-weight: 500;">{{ $data['name'] }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.06);">
                                                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Email</span><br>
                                                    <a href="mailto:{{ $data['email'] }}" style="font-size: 15px; color: #512731; text-decoration: none;">{{ $data['email'] }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;{{ $data['commune'] || $data['volume'] ? ' border-bottom: 1px solid rgba(0,0,0,0.06);' : '' }}">
                                                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Téléphone</span><br>
                                                    <a href="tel:{{ $data['phone'] }}" style="font-size: 15px; color: #512731; text-decoration: none;">{{ $data['phone'] }}</a>
                                                </td>
                                            </tr>
                                            @if ($data['commune'])
                                                <tr>
                                                    <td style="padding: 8px 0;{{ $data['volume'] ? ' border-bottom: 1px solid rgba(0,0,0,0.06);' : '' }}">
                                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Commune</span><br>
                                                        <span style="font-size: 15px; color: #222222;">{{ $data['commune'] }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($data['volume'])
                                                <tr>
                                                    <td style="padding: 8px 0;{{ $data['prestation'] ? ' border-bottom: 1px solid rgba(0,0,0,0.06);' : '' }}">
                                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Volume</span><br>
                                                        <span style="font-size: 15px; color: #222222;">{{ $data['volume'] }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($data['prestation'])
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">Prestation</span><br>
                                                        <span style="font-size: 15px; color: #222222;">{{ $data['prestation'] }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Message --}}
                            @if ($data['message'])
                                <div style="margin-bottom: 24px;">
                                    <p style="margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #999999;">
                                        Message
                                    </p>
                                    <div style="padding: 16px; background-color: #F9F6F2; border-radius: 8px; border-left: 3px solid #512731;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #333333; white-space: pre-line;">{{ $data['message'] }}</p>
                                    </div>
                                </div>
                            @endif

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-top: 8px;">
                                        <a href="mailto:{{ $data['email'] }}" style="display: inline-block; padding: 12px 28px; background-color: #512731; color: #ffffff; text-decoration: none; font-size: 14px; border-radius: 8px;">
                                            Répondre à {{ $data['name'] }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #EBE4DE; padding: 20px 40px; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #666666; line-height: 1.5;">
                                Ce message a été envoyé depuis le formulaire de contact de
                                <a href="https://chouchoute-toi.fr" style="color: #512731; text-decoration: none;">chouchoute-toi.fr</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
