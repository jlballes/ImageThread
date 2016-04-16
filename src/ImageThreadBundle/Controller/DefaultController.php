<?php

namespace ImageThreadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use ImageThreadBundle\Entity\Image;
use ImageThreadBundle\Form\ImageType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
    {	
    	$this->increaseVariable('views_count');

    	$image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // $file stores the uploaded image file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $image->getFilename();

            // Generate a unique name for the file before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // Move the file to the directory where images are stored
            $uploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
            $file->move($uploadDir, $fileName);

            $image->setFilename($fileName);

            $image->setCreated(new \DateTime());

            // persist objects
            $em = $this->getDoctrine()->getManager();
		    $em->persist($image);
		    $em->flush();

		    // increase posts
		    $this->increaseVariable('posts_count');

		    $this->addFlash('notice', 'Your image was saved!'); 

            return $this->redirect($this->generateUrl('home'));
        }

        return $this->render('ImageThreadBundle:Default:index.html.twig', array(
            'form' => $form->createView(),
            'images' => $this->getAllImages(),
        ));
    }

     /**
     * @Route("/export-csv", name="export_csv")
     */
    public function exportCsvAction(Request $request)
    {	
		$response = new StreamedResponse();
	    $response->setCallback(function(){
	 
	        $handle = fopen('php://output', 'w+');
	        // Add the header of the CSV file
	        fputcsv($handle, array('Title', 'Filename'));

	        $images = $this->getAllImages();
	        foreach ($images as $image) {
				fputcsv($handle, array($image->getTitle(), $image->getFilename()) );
			}
	 
	        fclose($handle);
	    });
	 
	    $response->setStatusCode(200);
	    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
	    $response->headers->set('Content-Disposition','attachment; filename="ImageThread.csv"');
	 
	    return $response;
    }

    /**
     * @Route("/export-excel", name="export_excel")
     */
    public function exportExcelAction(Request $request)
    {   
        // to create empty object
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        // assign properties
        $phpExcelObject->getProperties()
            ->setCreator("ImageThread")
            ->setLastModifiedBy("ImageThread")
            ->setTitle("ImageThreadExport")
            ->setSubject("ImageThread");

        //  active sheet
        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setTitle('ImageThreadSheet1');

        // write headers
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Title')
            ->setCellValue('B1', 'Filename');

        // write data
        $row = 2;
        $images = $this->getAllImages();
        foreach ($images as $image) {
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A'.$row, $image->getTitle())
                ->setCellValue('B'.$row, $image->getFilename());

            $row++;
        }

        // create writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'ImageThread.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }


    /**
     * Get all images
     */
    private function getAllImages(){
    	$repository = $this->getDoctrine()
		    ->getRepository('ImageThreadBundle:Image');

		$query = $repository->createQueryBuilder('i')
		    ->orderBy('i.created', 'DESC')
		    ->getQuery();

		return $query->getResult();
    }


    /**
     * Increase variable
     */
    private function increaseVariable($id){
	    $count = $this->getDoctrine()
        ->getRepository('ImageThreadBundle:Variable')
        ->find($id);

        $count->setValue($count->getValue() + 1);

        // persist objects
        $em = $this->getDoctrine()->getManager();
	    $em->persist($count);
	    $em->flush();
    }
}
