<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title>神ったー</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css"
        integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>

<body>
    <div class="p-help">
        <p class="p-help__description">「フォローキーワード」「いいねキーワード」には以下の指定方法を組み合わせて使用できます。</p>
        <table class="p-help__table">
            <thead>
                <tr class="p-help__row">
                    <th class="p-help__col">指定方法の例</th>
                    <th class="p-help__col">指定される検索条件</th>
                </tr>
            </thead>
            <tbody>
                <tr class="p-help__row">
                    <td class="p-help__col">日本 サッカー</td>
                    <td class="p-help__col">「日本」と「サッカー」という単語が含まれるツイート</td>
                </tr>
                <tr>
                    <td class="p-help__col">"今日のサッカー"</td>
                    <td class="p-help__col">「今日のサッカー」という単語をそのまま含むツイート</td>
                </tr>
                <tr>
                    <td class="p-help__col">プログラマ OR エンジニア</td>
                    <td class="p-help__col">「プログラマ」、あるいは「エンジニア」(又は両方)を含むツイート</td>
                </tr>
                <tr>
                    <td class="p-help__col">インド -カレー</td>
                    <td class="p-help__col">「インド」を含むが、「カレー」を含まないツイート</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>