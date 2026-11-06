{{-- A message to an account: a heading, a few sentences, one button and a note. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #F9F6F2; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F9F6F2; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td style="background-color: #512731; padding: 30px 40px; border-radius: 12px 12px 0 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: 3px;">
                                {{ config('mail.from.name') }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0 0 20px; font-size: 18px; font-weight: 700; color: #222222;">{{ $heading }}</p>

                            @foreach ($paragraphs as $paragraph)
                                <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #333333;">{{ $paragraph }}</p>
                            @endforeach

                            @if ($link !== null)
                                <p style="margin: 28px 0;">
                                    <a href="{{ $link }}" style="display: inline-block; padding: 14px 28px; background-color: #512731; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 700;">{{ $button }}</a>
                                </p>
                            @endif

                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #777777;">{{ $note }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
