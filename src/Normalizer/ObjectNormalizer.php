<?php namespace Firebase\Normalizer;

use Psr\Http\Message\ResponseInterface;

class ObjectNormalizer extends AbstractNormalizer implements NormalizerInterface {

    protected $name = 'object';

    public function normalize(ResponseInterface $response)
    {
        return json_decode($response->getBody()->__toString(), false);
    }

}
