<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();

if (!isset($_GET['file'])) {
    die("No file found.");
}

$file = basename($_GET['file']);
$filePath = "uploads/" . $file;

if (!file_exists($filePath)) {
    die("File Not Found");
}

$extension = strtolower(
    pathinfo($filePath, PATHINFO_EXTENSION)
);

$resumeText = "";

if ($extension == "pdf") {

    try {

        $pdf = $parser->parseFile($filePath);
        $resumeText = $pdf->getText();

    } catch (Exception $e) {

        die($e->getMessage());

    }

} else {

    $resumeText = "Image OCR not added.";

}

include "config.php";


/* =========================
   Gemini Prompt
========================= */

$prompt = "
Analyze this resume.

Return ONLY valid JSON.
Do not use markdown.
Do not use ```json.
Do not add any extra text.

{
  \"ats_score\":0,
  \"summary\":\"\",
  \"skills\":[],
  \"missing_skills\":[],
  \"suggestions\":[]
}

Resume:
$resumeText
";


/* =========================
   Gemini API Request
========================= */

$data = [

    "contents" => [

        [

            "parts" => [

                [

                    "text" => $prompt

                ]

            ]

        ]

    ]

];


$ch = curl_init($API_URL);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [

        "Content-Type: application/json",

        "x-goog-api-key: " . $API_KEY

    ]
);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($data)
);


$response = curl_exec($ch);


/* =========================
   CURL Error
========================= */

if ($response === false) {

    die(
        "<h2>API Connection Error</h2>" .
        "<p>" .
        curl_error($ch) .
        "</p>"
    );

}

curl_close($ch);


/* =========================
   Gemini Response
========================= */

$responseData = json_decode(
    $response,
    true
);


if (
    !isset(
        $responseData['candidates'][0]['content']['parts'][0]['text']
    )
) {

    echo "<h2>Gemini API Response Error</h2>";

    echo "<pre>";

    print_r($responseData);

    echo "</pre>";

    exit;

}


$text =
    $responseData['candidates'][0]['content']['parts'][0]['text'];


/* =========================
   Clean JSON
========================= */

$text = str_replace(
    "```json",
    "",
    $text
);

$text = str_replace(
    "```",
    "",
    $text
);

$text = trim($text);


$result = json_decode(
    $text,
    true
);


if (!$result) {

    die(

        "<h2>JSON Error</h2>" .

        "<pre>" .

        json_last_error_msg() .

        "\n\n" .

        htmlspecialchars($text) .

        "</pre>"

    );

}


/* =========================
   ATS SCORE
========================= */

$atsScore = isset(
    $result['ats_score']
)
? (int)$result['ats_score']
: 0;


/* Score 0 - 100 */

$atsScore = max(
    0,
    min(
        100,
        $atsScore
    )
);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    ATS Resume Analysis
</title>


<style>

/* =========================
   RESET
========================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

}


/* =========================
   BODY
========================= */

body {

    background: #94d8eb;

    color: #1e293b;

    padding: 40px 20px;

}


/* =========================
   MAIN CONTAINER
========================= */

.container {

    width: 92%;

    max-width: 1100px;

    margin: auto;

}


/* =========================
   HEADER
========================= */

.top {

    background: white;

    padding: 30px;

    border-radius: 20px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.06);

    margin-bottom: 25px;

}


.top h1 {

    font-size: 28px;

    color: #111827;

}


.top p {

    margin-top: 7px;

    color: #64748b;

}


/* =========================
   ATS SCORE AREA
========================= */

.score-box {

    margin-top: 25px;

}


.score-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 10px;

}


.score-title {

    font-size: 18px;

    font-weight: 600;

}


.score-number {

    font-size: 28px;

    font-weight: bold;

    color: #2563eb;

}


/* =========================
   PROGRESS BAR
========================= */

.progress-container {

    width: 100%;

    height: 20px;

    background: #6da3ea;

    border-radius: 50px;

    overflow: hidden;

}


.progress-bar {

    height: 100%;

    width: 0%;

    background:
        linear-gradient(
            90deg,
            #2563eb,
            #4f46e5
        );

    border-radius: 50px;

    transition:
        width 2s ease-in-out;

}


/* =========================
   SCORE MESSAGE
========================= */

.score-message {

    margin-top: 12px;

    color: #64748b;

}


/* =========================
   SUMMARY
========================= */

.summary {

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    color: white;

    padding: 30px;

    border-radius: 18px;

    margin-bottom: 25px;

}


.summary h2 {

    margin-bottom: 12px;

}


.summary p {

    line-height: 1.7;

}


/* =========================
   GRID
========================= */

.grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 25px;

    margin-bottom: 25px;

}


/* =========================
   CARD
========================= */

.card {

    background: white;

    padding: 25px;

    border-radius: 18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.05);

}


.card h2 {

    margin-bottom: 20px;

    font-size: 20px;

}


/* =========================
   SKILLS
========================= */

.skill-list {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

}


.skill {

    padding: 9px 15px;

    border-radius: 30px;

    font-size: 14px;

    font-weight: 600;

}


.good {

    background: #dcfce7;

    color: #15803d;

}


.bad {

    background: #fee2e2;

    color: #dc2626;

}


/* =========================
   SUGGESTIONS
========================= */

.suggestions {

    background: white;

    padding: 30px;

    border-radius: 18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.05);

}


.suggestions h2 {

    margin-bottom: 20px;

}


.item {

    display: flex;

    gap: 15px;

    padding: 17px;

    margin-bottom: 12px;

    background: #f8fafc;

    border-radius: 12px;

}


.icon {

    width: 35px;

    height: 35px;

    border-radius: 50%;

    background: #2563eb;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

    flex-shrink: 0;

}


.item p {

    line-height: 1.6;

    color: #475569;

}


/* =========================
   BUTTON
========================= */

.actions {

    text-align: center;

    margin-top: 25px;

}


button {

    padding: 13px 28px;

    border: none;

    border-radius: 10px;

    background: #2563eb;

    color: white;

    font-size: 15px;

    font-weight: 600;

    cursor: pointer;

}


button:hover {

    background: #1d4ed8;

}


/* =========================
   MOBILE
========================= */

@media(max-width: 700px) {

    body {

        padding: 20px 10px;

    }


    .container {

        width: 100%;

    }


    .grid {

        grid-template-columns:
            1fr;

    }


    .top {

        padding: 22px;

    }

}

</style>

</head>


<body>


<div class="container">


<!-- =========================
     HEADER
========================= -->

<div class="top">

    <h1>
        ATS Resume Analysis
    </h1>

    <p>
        AI-powered Resume Screening Report
    </p>


    <!-- =========================
         ATS SCORE
    ========================= -->

    <div class="score-box">


        <div class="score-header">


            <div class="score-title">

                ATS Compatibility Score

            </div>


            <div
                class="score-number"
                id="scoreNumber"
            >

                0 / 100

            </div>


        </div>


        <div class="progress-container">


            <div
                class="progress-bar"
                id="progressBar"
            ></div>


        </div>


        <p
            class="score-message"
            id="scoreMessage"
        >

            Analyzing your resume...

        </p>


    </div>


</div>



<!-- =========================
     SUMMARY
========================= -->

<div class="summary">


    <h2>
        📄 Resume Summary
    </h2>


    <p>

        <?php

        echo htmlspecialchars(
            $result['summary'] ?? ''
        );

        ?>

    </p>


</div>



<!-- =========================
     SKILLS
========================= -->

<div class="grid">


    <!-- FOUND SKILLS -->

    <div class="card">


        <h2>
            ✓ Skills Found
        </h2>


        <div class="skill-list">


            <?php

            if (
                !empty(
                    $result['skills']
                )
            ) {

                foreach (
                    $result['skills']
                    as $skill
                ) {

                    echo

                    '<span class="skill good">'
                    .
                    htmlspecialchars(
                        $skill
                    )
                    .
                    '</span>';

                }

            }

            ?>


        </div>


    </div>



    <!-- MISSING SKILLS -->

    <div class="card">


        <h2>
            ⚠ Missing Skills
        </h2>


        <div class="skill-list">


            <?php

            if (
                !empty(
                    $result['missing_skills']
                )
            ) {

                foreach (
                    $result['missing_skills']
                    as $skill
                ) {

                    echo

                    '<span class="skill bad">'
                    .
                    htmlspecialchars(
                        $skill
                    )
                    .
                    '</span>';

                }

            }

            ?>


        </div>


    </div>


</div>



<!-- =========================
     SUGGESTIONS
========================= -->

<div class="suggestions">


    <h2>
        💡 How to Improve Your Resume
    </h2>


    <?php

    if (
        !empty(
            $result['suggestions']
        )
    ) {


        $count = 1;


        foreach (
            $result['suggestions']
            as $suggestion
        ) {


    ?>


    <div class="item">


        <div class="icon">

            <?php

            echo $count;

            ?>

        </div>


        <p>

            <?php

            echo htmlspecialchars(
                $suggestion
            );

            ?>

        </p>


    </div>


    <?php


            $count++;


        }


    }

    ?>


</div>



<!-- =========================
     BUTTON
========================= -->

<div class="actions">


    <button
        onclick="window.print()"
    >

        🖨 Print / Save Report

    </button>


</div>


</div>



<!-- =========================
     ANIMATION
========================= -->

<script>


const finalScore =

    <?php

    echo $atsScore;

    ?>;


const progressBar =

    document.getElementById(
        "progressBar"
    );


const scoreNumber =

    document.getElementById(
        "scoreNumber"
    );


const scoreMessage =

    document.getElementById(
        "scoreMessage"
    );


/*
   Animate Score
*/

let currentScore = 0;


const animation =

    setInterval(

        function() {


            currentScore++;


            /*
               Update Number
            */

            scoreNumber.innerText =

                currentScore
                +
                " / 100";


            /*
               Update Progress Bar
            */

            progressBar.style.width =

                currentScore
                +
                "%";


            /*
               Stop Animation
            */

            if (

                currentScore
                >=
                finalScore

            ) {


                clearInterval(
                    animation
                );


                /*
                   Score Message
                */

                if (
                    finalScore >= 80
                ) {

                    scoreMessage.innerText =
                        "Excellent! Your resume is highly ATS-friendly.";

                }

                else if (
                    finalScore >= 60
                ) {

                    scoreMessage.innerText =
                        "Good resume. A few improvements can increase your ATS score.";

                }

                else if (
                    finalScore >= 40
                ) {

                    scoreMessage.innerText =
                        "Your resume needs some improvements to become more ATS-friendly.";

                }

                else {

                    scoreMessage.innerText =
                        "Your resume needs significant improvements for better ATS compatibility.";

                }


            }


        },

        20

    );


</script>


</body>

</html>