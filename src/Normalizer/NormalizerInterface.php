<?php namespace Firebase\Normalizer;


use Psr\Http\Message\ResponseInterface;

interface NormalizerInterface {

    public function normalize(ResponseInterface $response);

    public function getName();

}
