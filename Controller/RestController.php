<?php

namespace Symfony\Cmf\Bundle\CreateBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Routing\Exception\ResourceNotFoundException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Validator\ValidatorInterface;

use FOS\RestBundle\View\ViewHandlerInterface,
    FOS\RestBundle\View\View,
    FOS\Rest\Util\Codes;

use Midgard\CreatePHP\Metadata\RdfTypeFactory,
    Midgard\CreatePHP\RestService,
    Midgard\CreatePHP\RdfMapperInterface;

class RestController
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @var string the role name for the security check
     */
    protected $requiredRole;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var RdfMapperInterface
     */
    protected $rdfMapper;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param \FOS\RestBundle\View\ViewHandlerInterface $viewHandler
     * @param \Symfony\Component\Validator\ValidatorInterface $validator
     * @param \Midgard\CreatePHP\RdfMapperInterface $rdfMapper
     * @param \Midgard\CreatePHP\Metadata\RdfTypeFactory $typeFactory
     * @param \Midgard\CreatePHP\RestService $restHandler
     * @param string $requiredRole the role to check with the securityContext
     *      (if you pass one), defaults to everybody: IS_AUTHENTICATED_ANONYMOUSLY
     * @param \Symfony\Component\Security\Core\SecurityContextInterface|null $securityContext
     *      the security context to use to check for the role. No security
     *      check if this is null
     *
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        ValidatorInterface $validator,
        RdfMapperInterface $rdfMapper,
        RdfTypeFactory $typeFactory,
        RestService $restHandler,
        $requiredRole = "IS_AUTHENTICATED_ANONYMOUSLY",
        SecurityContextInterface $securityContext = null
    ) {
        $this->viewHandler = $viewHandler;
        $this->validator = $validator;
        $this->rdfMapper = $rdfMapper;
        $this->typeFactory = $typeFactory;
        $this->restHandler = $restHandler;
        $this->requiredRole = $requiredRole;
        $this->securityContext = $securityContext;
    }

    /**
     * Handle article PUT
     */
    public function putDocumentAction(Request $request, $subject)
    {
        if ($this->securityContext && false === $this->securityContext->isGranted($this->requiredRole)) {
            throw new AccessDeniedException();
        }

        $model = $this->rdfMapper->getBySubject($subject);

        if (empty($model)) {
            throw new ResourceNotFoundException($subject.' not found');
        }

        $type = $this->typeFactory->getType(get_class($model));
        $result = $this->restHandler->run($request->request->all(), $type, null, RestService::HTTP_PUT);

        $view = View::create($result)->setFormat('json');
        return $this->viewHandler->handle($view, $request);
    }
}