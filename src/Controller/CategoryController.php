<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pyrrah\OpenWeatherMapBundle\Services\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Pyrrah\OpenWeatherMapBundle\Services\ClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;



/**
 * @Route("/category")
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/", name="app_category_index", methods={"GET"})
     */
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * @Route("/export", name="app_category_export", methods={"GET"})
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        // Создаем новый объект Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Записываем заголовки столбцов
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'name');
        $sheet->setCellValue('C1', 'country');

        // Записываем данные категорий
        $row = 2;
        foreach ($categories as $category) {
            $sheet->setCellValue('A' . $row, $category->getId());
            $sheet->setCellValue('B' . $row, $category->getName());
            $sheet->setCellValue('C' . $row, $category->getCountry());

            $row++;
        }

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $fileName = 'categories.xlsx';
        $tempFilePath = sys_get_temp_dir() . '/' . $fileName;
        $writer->save($tempFilePath);

        // Создаем объект Response с заголовками для скачивания файла
        $response = new Response(file_get_contents($tempFilePath));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        // Удаляем временный файл
        unlink($tempFilePath);

        return $response;
    }

    /**
     * @Route("/new", name="app_category_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CategoryRepository $categoryRepository): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryRepository->add($category, true);

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_category_show", methods={"GET"})
     * @ParamConverter("Сategory", class="App\Entity\Category")
     */
    public function show(Category $category, AdapterInterface $customCache, HttpClientInterface $httpClient): Response
    {
        // Название города из объекта категории
        $city = $category->getCountry();

        // Проверка, есть ли закешированный ответ о погоде для данного города
        $cacheKey = 'weather_' . $city;
        $cachedResponse = $customCache->getItem($cacheKey);
        if (!$cachedResponse->isHit()) {
            try {
                // Запрос к API погоды с помощью Symfony HttpClient
                $apiKey = '32ba9bc73702fda7186e07fd974186c5';
                $response = $httpClient->request('GET', 'http://api.openweathermap.org/data/2.5/weather', [
                    'query' => [
                        'q' => $city,
                        'appid' => $apiKey,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    throw new HttpException($statusCode, 'Не удалось получить данные о погоде');
                }

                $weatherResponse = $response->toArray();
            } catch (\Exception $e) {
                $weatherResponse = null;
            }

            // Сохранить ответ API в кэше на 1 час
            $cachedResponse->set($weatherResponse);
            $cachedResponse->expiresAfter(3600);
            $customCache->save($cachedResponse);
        } else {
            // Если ответ закеширован, используем закешированные данные
            $weatherResponse = $cachedResponse->get();
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'weather' => $weatherResponse, // Передаем данные о погоде в шаблон
        ]);
    }


    /**
     * @Route("/{id}/edit", name="app_category_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Category $category, CategoryRepository $categoryRepository): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryRepository->add($category, true);

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_category_delete", methods={"POST"})
     */
    public function delete(Request $request, Category $category, CategoryRepository $categoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $categoryRepository->remove($category, true);
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
