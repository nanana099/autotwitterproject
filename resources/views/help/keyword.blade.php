<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>キーワード指定方法</title>
</head>

<body>
    <div>
        <div>
            <p>「フォローキーワード」「いいねキーワード」には以下の演算子を組み合わせて使用できます。</p>
            <table>
                <thead>
                    <tr>
                        <th>演算子の例</th>
                        <th>指定される検索条件</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>日本 サッカー</td>
                        <td>「日本」と「サッカー」という単語が含まれるツイート</td>
                    </tr>
                    <tr>
                        <td>"今日のサッカー"</td>
                        <td>「今日のサッカー」という単語をそのまま含むツイート</td>
                    </tr>
                    <tr>
                        <td>プログラマ OR エンジニア</td>
                        <td>「プログラマ」、あるいは「エンジニア」(又は両方)を含むツイート</td>
                    </tr>
                    <tr>
                        <td>インド -カレー</td>
                        <td>「インド」を含むが、「カレー」を含まないツイート</td>
                    </tr>
                    {{-- <tr>
                        <td colspan="2"><b>それぞれ併用も可能です</b></td>
                    </tr>
                    <tr>
                        <td>インド -カレー</td>
                        <td>日本</td>
                    </tr> --}}
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>