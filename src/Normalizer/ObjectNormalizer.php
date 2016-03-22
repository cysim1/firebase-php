<?php namespace Firebase\Normalizer;

use Psr\Http\Message\ResponseInterface;

class ObjectNormalizer extends AbstractNormalizer implements NormalizerInterface {

    protected $name = 'object';

    public function normalize(ResponseInterface $response)
    {
        return $response->json(array('object' => true));
    }

}
