<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Laasti\Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of Stack
 *
 * @author Sonia
 */
class SimpleStack implements StackInterface
{

    /**
     * Contains the registered middlewares
     * @var array
     */
    protected $middlewares = [];

    /**
     * Contains the registered terminable middlewares
     * @var array
     */
    protected $terminableMiddlewares = [];

    /**
     * Adds the middleware at the beginning.
     * You can pass additional arguments to the method. They will be used when calling handle/close methods.
     *
     * @param MiddlewareInterface $middleware
     * @return \Laasti\Stack\SimpleStack
     */
    public function unshift($middleware)
    {
        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('The first argument must be an instance of MiddlewareInterface.');
        }

        array_unshift($this->middlewares, func_get_args());

        if ($middleware instanceof MiddlewareTerminableInterface) {
            array_unshift($this->terminableMiddlewares, func_get_args());
        }
        return $this;
    }

    //TODO: Should it be permitted for a terminate only middleware to be added?
    //Maybe it would be better to have a middleware that calls those terminate only objects
    /**
     * Adds the middleware at the end.
     * You can pass additional arguments to the method. They will be used when calling handle/close methods.
     *
     * @param MiddlewareInterface $middleware
     * @return \Laasti\Stack\SimpleStack
     */
    public function push($middleware)
    {
        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('The first argument must be an instance of MiddlewareInterface.');
        }

        array_push($this->middlewares, func_get_args());

        if ($middleware instanceof MiddlewareTerminableInterface) {
            array_push($this->terminableMiddlewares, func_get_args());
        }

        return $this;
    }

    /**
     * Loops through all registered middlewares until a response is returned.
     *
     * @throws StackException When no response is returned
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request)
    {
        $key = 0;
        $next_middleware_spec = $this->middlewares[$key];

        while ($next_middleware_spec) {
            //Get the middleware
            $middleware = array_shift($next_middleware_spec);
            //Put request as first parameter for the middleware
            array_unshift($next_middleware_spec, $request);

            $return = call_user_func_array([$middleware, 'handle'], $next_middleware_spec);

            if ($return instanceof Response) {
                return $return;
            }
            $key++;
            $next_middleware_spec = isset($this->middlewares[$key]) ? $this->middlewares[$key] : false;
        }

        throw new StackException('No response generated by the middleware stack.');
    }

    /**
     * Loops through all TerminableMiddleware
     *
     * @param Request $request
     * @param Response $response
     */
    public function close(Request $request, Response $response)
    {

        $inverted = array_reverse($this->terminableMiddlewares);

        foreach ($inverted as $spec) {
            $middleware = array_shift($spec);

            $args = array_merge(array($request, $response), $spec);

            call_user_func_array([$middleware, 'terminate'], $args);
        }
    }

}

