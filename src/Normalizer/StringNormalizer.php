<?php namespace Firebase\Normalizer;


use Psr\Http\Message\ResponseInterface;

class StringNormalizer extends AbstractNormalizer implements NormalizerInterface {

    protected $name = 'string';

    public function normalize(ResponseInterface $response)
    {
        return $response->getBody();
    }

}
