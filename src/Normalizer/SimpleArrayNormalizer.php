<?php namespace Firebase\Normalizer;


use Psr\Http\Message\ResponseInterface;

class SimpleArrayNormalizer extends AbstractNormalizer implements NormalizerInterface {

    protected $name = 'simple';

    public function normalize(ResponseInterface $response)
    {
        return array_values(json_decode($response->getBody()->__toString(), true));
    }

}
