<?php namespace Firebase\Normalizer;


use Psr\Http\Message\ResponseInterface;

class AssocArrayNormalizer extends AbstractNormalizer implements NormalizerInterface {

    protected $name = 'assoc';

    public function normalize(ResponseInterface $response)
    {
        return json_decode($response->getBody()->__toString(), true);
    }
}
