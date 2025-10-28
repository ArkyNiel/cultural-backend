<?php
require '../../../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// def
function error422($message){
    $data = [
        'status' => 422,
        'message' => $message,
    ];
    header("HTTP/1.0 422 Unprocessable Entity"); 
    echo json_encode($data);
    exit();
}

// store vocal score
function storeVocalScore($scoreInput){
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return error422('User not logged in');
    }

    if (!isset($scoreInput['cand_id']) || empty(trim($scoreInput['cand_id']))) {
        return error422('Enter candidate ID');
    }
    if (!isset($scoreInput['voice_tone_quality']) || empty(trim($scoreInput['voice_tone_quality']))) {
        return error422('Enter voice/tone quality score');
    }
    if (!isset($scoreInput['mastery_and_timing']) || empty(trim($scoreInput['mastery_and_timing']))) {
        return error422('Enter mastery and timing score');
    }
    if (!isset($scoreInput['vocal_expression']) || empty(trim($scoreInput['vocal_expression']))) {
        return error422('Enter vocal expression score');
    }
    if (!isset($scoreInput['diction']) || empty(trim($scoreInput['diction']))) {
        return error422('Enter diction score');
    }
    if (!isset($scoreInput['stage_presence']) || empty(trim($scoreInput['stage_presence']))) {
        return error422('Enter stage presence score');
    }
    if (!isset($scoreInput['entertainment_value']) || empty(trim($scoreInput['entertainment_value']))) {
        return error422('Enter entertainment value score');
    }

    $cand_id = mysqli_real_escape_string($conn, $scoreInput['cand_id']);
    $judge_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $voice_tone_quality = mysqli_real_escape_string($conn, $scoreInput['voice_tone_quality']);
    $mastery_and_timing = mysqli_real_escape_string($conn, $scoreInput['mastery_and_timing']);
    $vocal_expression = mysqli_real_escape_string($conn, $scoreInput['vocal_expression']);
    $diction = mysqli_real_escape_string($conn, $scoreInput['diction']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);
    $entertainment_value = mysqli_real_escape_string($conn, $scoreInput['entertainment_value']);

    // Generate unique score_id
    do {
        $score_id = rand(100000, 999999);
        $checkQuery = "SELECT score_id FROM vocal_score WHERE score_id = '$score_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $query = "INSERT INTO vocal_score (score_id, cand_id, judge_id, `voice/tone_quality`, mastery_and_timing, vocal_expression, diction, stage_presence, entertainment_value)
              VALUES ('$score_id', '$cand_id', '$judge_id', '$voice_tone_quality', '$mastery_and_timing', '$vocal_expression', '$diction', '$stage_presence', '$entertainment_value')";
    $result = mysqli_query($conn, $query);

    if($result){
        // Calculate average total_score from all judges submitted so far for this contestant
        $avg_query = "SELECT AVG(total_score) AS avg_total FROM vocal_score WHERE cand_id = '$cand_id'";
        $avg_result = mysqli_query($conn, $avg_query);
        $avg_row = mysqli_fetch_assoc($avg_result);
        $avg_total = $avg_row['avg_total'];

        // The final score is the average total_score (sum of all judges' total_scores divided by number of judges)
        $percentage = $avg_total;

        // Check if vocal_final_score row exists for this cand_id
        $check_query = "SELECT cand_id FROM vocal_final_score WHERE cand_id = '$cand_id'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing row
            $update_query = "UPDATE vocal_final_score SET final_score = '$percentage' WHERE cand_id = '$cand_id'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new row
            $insert_query = "INSERT INTO vocal_final_score (cand_id, final_score) VALUES ('$cand_id', '$percentage')";
            mysqli_query($conn, $insert_query);
        }

        $data = [
            'status' => 201,
            'message' => 'Vocal Score Created Successfully',
            'has_submitted' => isset($_SESSION['has_submitted']) ? (bool)$_SESSION['has_submitted'] : false
        ];
        header("HTTP/1.0 201 Created");
        return json_encode($data);
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

// READ - Get All Vocal Scores
function getAllVocalScores($params = []){
    global $conn;

    $whereClause = "";
    if (isset($params['team']) && !empty(trim($params['team']))) {
        $team = mysqli_real_escape_string($conn, $params['team']);
        $whereClause = "WHERE vc.cand_team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.cand_id,
                vc.cand_name,
                vc.cand_team,
                vs.judge_id,
                vs.`voice/tone_quality`,
                vs.mastery_and_timing,
                vs.vocal_expression,
                vs.diction,
                vs.stage_presence,
                vs.entertainment_value,
                vs.total_score
              FROM vocal_score vs
              INNER JOIN vocal_contestants vc ON vs.cand_id = vc.cand_id
              $whereClause
              ORDER BY vs.judge_id, vs.total_score DESC";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // Group scores by judge_id
            $groupedScores = [];
            foreach ($res as $score) {
                $judge_id = $score['judge_id'];
                if (!isset($groupedScores['judge_' . $judge_id])) {
                    $groupedScores['judge_' . $judge_id] = [];
                }
                $groupedScores['judge_' . $judge_id][] = $score;
            }

            $data = [
                'status' => 200,
                'message' => 'Vocal Scores for All Judges Fetched Successfully',
                'data' => $groupedScores
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Vocal Scores Found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

// READ - Get Vocal Score by score_id
function getVocalScores($scoreParams){
    global $conn;
    
    $score_id = mysqli_real_escape_string($conn, $scoreParams['score_id']);
    
    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }
    
    $query = "SELECT
                vs.score_id,
                vs.cand_id,
                vc.cand_name,
                vc.cand_team,
                vs.`voice/tone_quality`,
                vs.mastery_and_timing,
                vs.vocal_expression,
                vs.diction,
                vs.stage_presence,
                vs.entertainment_value,
                vs.total_score
              FROM vocal_score vs
              INNER JOIN vocal_contestants vc ON vs.cand_id = vc.cand_id
              WHERE vs.score_id = '$score_id' LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if($result){
        if(mysqli_num_rows($result) == 1){
            $res = mysqli_fetch_assoc($result);
            
            $data = [
                'status' => 200,
                'message' => 'Vocal Score Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Vocal Scores Found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

// READ - Get Vocal Score by cand_id
function getVocalScoreByCandId($scoreParams){
    global $conn;

    $cand_id = mysqli_real_escape_string($conn, $scoreParams['cand_id']);

    if(empty(trim($cand_id))){
        return error422('Enter candidate ID');
    }

    $query = "SELECT
                vs.score_id,
                vs.cand_id,
                vc.cand_name,
                vc.cand_team,
                vs.`voice/tone_quality`,
                vs.mastery_and_timing,
                vs.vocal_expression,
                vs.diction,
                vs.stage_presence,
                vs.entertainment_value,
                vs.total_score,
                vs.created_at
              FROM vocal_score vs
              INNER JOIN vocal_contestants vc ON vs.cand_id = vc.cand_id
              WHERE vs.cand_id = '$cand_id' LIMIT 1";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) == 1){
            $res = mysqli_fetch_assoc($result);

            $data = [
                'status' => 200,
                'message' => 'Vocal Score Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Vocal Score Found for this Candidate',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}


// UPDATE VOCAL SCORE *ONLY THE CHAIRMAN CAN UPDATE OR EDIT
function updateVocalScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);
    $voice_tone_quality = mysqli_real_escape_string($conn, $scoreInput['voice_tone_quality']);
    $mastery_and_timing = mysqli_real_escape_string($conn, $scoreInput['mastery_and_timing']);
    $vocal_expression = mysqli_real_escape_string($conn, $scoreInput['vocal_expression']);
    $diction = mysqli_real_escape_string($conn, $scoreInput['diction']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);
    $entertainment_value = mysqli_real_escape_string($conn, $scoreInput['entertainment_value']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }elseif(empty(trim($voice_tone_quality))){
        return error422('Enter voice/tone quality score');
    }elseif(empty(trim($mastery_and_timing))){
        return error422('Enter mastery and timing score');
    }elseif(empty(trim($vocal_expression))){
        return error422('Enter vocal expression score');
    }elseif(empty(trim($diction))){
        return error422('Enter diction score');
    }elseif(empty(trim($stage_presence))){
        return error422('Enter stage presence score');
    }elseif(empty(trim($entertainment_value))){
        return error422('Enter entertainment value score');
    }else{

        $query = "UPDATE vocal_score SET
                    `voice/tone_quality` = '$voice_tone_quality',
                    mastery_and_timing = '$mastery_and_timing',
                    vocal_expression = '$vocal_expression',
                    diction = '$diction',
                    stage_presence = '$stage_presence',
                    entertainment_value = '$entertainment_value'
                  WHERE score_id = '$score_id' LIMIT 1";

        $result = mysqli_query($conn, $query);

        if($result){
            $data = [
                'status' => 200,
                'message' => 'Vocal Score Updated Successfully',
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 500,
                'message' => 'Internal Server Error',
            ];
            header("HTTP/1.0 500 Internal Server Error");
            return json_encode($data);
        }
    }
}

// DELETE
function deleteVocalScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }

    $query = "DELETE FROM vocal_score WHERE score_id = '$score_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if($result){
        $data = [
            'status' => 200,
            'message' => 'Vocal Score Deleted Successfully',
        ];
        header("HTTP/1.0 200 OK");
        return json_encode($data);
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

// READ - Get Vocal Scores by Judge ID
function getVocalScoresByJudge($judgeParams){
    global $conn;

    $judge_id = mysqli_real_escape_string($conn, $judgeParams['judge_id']);

    if(empty(trim($judge_id))){
        return error422('Enter judge ID');
    }

    $whereClause = "WHERE vs.judge_id = '$judge_id'";
    if (isset($judgeParams['team']) && !empty(trim($judgeParams['team']))) {
        $team = mysqli_real_escape_string($conn, $judgeParams['team']);
        $whereClause .= " AND vc.cand_team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.cand_id,
                vc.cand_name,
                vc.cand_team,
                vs.`voice/tone_quality`,
                vs.mastery_and_timing,
                vs.vocal_expression,
                vs.diction,
                vs.stage_presence,
                vs.entertainment_value,
                vs.total_score,
                vs.created_at
              FROM vocal_score vs
              INNER JOIN vocal_contestants vc ON vs.cand_id = vc.cand_id
              $whereClause
              ORDER BY vs.created_at DESC";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Vocal Scores for Judge Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Vocal Scores Found for this Judge',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}
?>