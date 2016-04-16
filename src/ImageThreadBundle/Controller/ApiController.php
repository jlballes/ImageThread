<?php

namespace ImageThreadBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

class ApiController extends FOSRestController
{
    public function getViewsAction()
    {   
        $result = $this->getDoctrine()
            ->getRepository('ImageThreadBundle:Variable')
            ->find('views_count');

        return $this->view(array('views_count' => $result->getValue()));

    } // "get_views"            [GET] /views

    public function getPostsAction()
    {   
        $result = $this->getDoctrine()
            ->getRepository('ImageThreadBundle:Variable')
            ->find('posts_count');

        return $this->view(array('posts_count' => $result->getValue()));

    } // "get_posts"            [GET] /posts
}
