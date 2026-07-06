<?php

class_exists('Net_Ollama_Response') || require_once 'Net/Ollama/Response.php';

class Net_Ollama_Response_Embed extends Net_Ollama_Response
{
    var $embeddings = array();
    var $model;
    var $total_duration;
    var $load_duration;
    var $prompt_eval_count;
    var $prompt_eval_duration;
}
