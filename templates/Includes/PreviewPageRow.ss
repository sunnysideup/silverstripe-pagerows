<!DOCTYPE html>
<html lang="en">
<head>
    $ExtendedMetatags
    <% include WebpackCSSLinks %>
</head>
<body id="body-for-{$ClassNameToLower}" class="$CalculatedHeaderStyle">

    <div class="container">
    <div class="typography">
    <h1 class="purple">PREVIEW ONLY</h1>
    </div>
    </div>

    <main>
    <% loop PageRowsReadyForPublication %>
        $HTMLOutputAlwaysOutput
    <% end_loop %>
    </main>

    <% include WebpackJSLinks %>
    <script src="https://use.typekit.net/lmk3aiv.js"></script>
    <script>try{Typekit.load({ async: true });}catch(e){}</script>
</body>
</html>
