<?php
/**
 * This file is part of export bundle.
 * Created by Trimech Mehdi.
 * Date: 5/29/17
 * Time: 13:47
 * @author: Trimech Mehdi <http://trimech-mahdi.fr//>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Medooch\Bundles\ExportBundle\Controller;

use Medooch\Components\Helper\Yml\YamlManipulator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ExportController
 * @package Medooch\Bundles\ExportBundle\Controller
 */
class ExportController extends Controller
{
    /**
     * ---------------------------------------
     * @author: Trimech Mehdi <http://trimech-mahdi.fr//>
     * ---------------------------------------
     * **************** Function documentation: ****************
     * Export Action from entity to CSV
     * ---------------------------------------
     * **************** Function Annotation: ****************
     * @Route("/{entity}", name="medooch_export_csv")
     * ---------------------------------------
     * **************** Function input: ****************
     * @param $entity
     * ---------------------------------------
     * **************** Function output: ****************
     * @return StreamedResponse
     * ---------------------------------------
     */
    public function exportAction($entity)
    {
        /** check if the $entity is defined in config */
        $filename = $this->get('kernel')->getRootDir() . '/app/config/export.yml';
        $entities = YamlManipulator::getFileContents($filename);

        if (is_null($entities)) {
            throw $this->createNotFoundException($filename . ' not found.');
        }

        if (!isset($entities[$entity])) {
            throw $this->createNotFoundException($entity . ' is not defined in the configuration');
        }
        $configuration = $entities[$entity];
        $queryBuilder = $configuration['query'];

        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository($configuration['class'])->createQueryBuilder('e');

        $translator = $this->get('translator');

        /** @var query joins from the declaration $queryBuilder */
        if (isset($queryBuilder['join'])) {
            foreach ($queryBuilder['join'] as $join) {
                $params = explode(',', $join);
                if (!is_array($params)) {
                    throw $this->createNotFoundException('Invalid declaration of query under ' . $entity . '. Please check the documentation');
                }
                $query->join($params[0], $params[1]);
            }
        }

        $headers = [];
        /** query selector */
        if (isset($queryBuilder['select']) && !empty($queryBuilder['select'])) {
            $query->select($queryBuilder['select'][0]);
            if (count($queryBuilder['select'])) {
                foreach ($queryBuilder['select'] as $select) {
                    $headers[] = str_replace('e.', '', $select);
                    $query->addSelect($select);
                }
            }
        } else {
            $query->select('e.id');
        }

        /** query conditions */
        $query->where('e.id IS NOT NULL');
        if (isset($queryBuilder['where']) && isset($queryBuilder['parameters'])) {
            foreach ($queryBuilder['where'] as $condition) {
                $query->andWhere($condition);
            }
            foreach ($queryBuilder['parameters'] as $parameter) {
                if ($parameter == 'week') {
                    $week = date("W", strtotime('now'));
                    $query->setParameter('week', $week);
                }
                if ($parameter == 'status') {
                    $query->setParameter('status', true);
                }
            }
        }

        /** query group by */
        if (isset($queryBuilder['groupBy']) && !empty($queryBuilder['groupBy'])) {
            $groupBy = '';
            foreach ($queryBuilder['groupBy'] as $key => $queryGroup) {
                $key++;
                $groupBy .= $queryGroup;
                if ($key < count($queryBuilder['groupBy'])) {
                    $groupBy .= ',';
                }
            }
            $query->groupBy($groupBy);
        }

        /** query order by */
        if (isset($queryBuilder['orderBy']) && !empty($queryBuilder['orderBy'])) {
            foreach ($queryBuilder['orderBy'] as $key => $orderBy) {
                $params = explode(',', $orderBy);
                if (!is_array($params)) {
                    throw $this->createNotFoundException('Invalid declaration of query under ' . $entity . '. Please check the documentation');
                }
                $query->addOrderBy($params[0], $params[1]);
            }
        }

        /** @var get query Result $results */
        $results = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($headers, $results, $translator) {
            $handle = fopen('php://output', 'w+');

            // Add the header of the CSV file
            fputcsv($handle, array_values($headers), ';');
            // Query data from database
            // Add the data queried from database
            foreach ($results as $result) {
                if (isset($result['isActive']) || isset($result['isDeleted'])) {
                    if ($result['isActive']) {
                        $result['isActive'] = $translator->trans('yes');
                    } else {
                        $result['isActive'] = $translator->trans('no');
                    }
                    if ($result['isDeleted']) {
                        $result['isDeleted'] = $translator->trans('yes');
                    } else {
                        $result['isDeleted'] = $translator->trans('no');
                    }
                }
                fputcsv(
                    $handle, // The file pointer
                    $result, // The fields
                    ';' // The delimiter
                );
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $now = new \DateTime();
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity . '_' . $now->format('d-m-y h:m:s') . '.csv"');

        return $response;
    }
}
