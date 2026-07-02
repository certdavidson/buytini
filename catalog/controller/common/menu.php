<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					// Level 3
					$grandchildren_data = array();

					$grandchildren = $this->model_catalog_category->getCategories($child['category_id']);

					foreach ($grandchildren as $grandchild) {
						$filter_data_2 = array(
							'filter_category_id'  => $grandchild['category_id'],
							'filter_sub_category' => true
						);

						$grandchildren_data[] = array(
							'name'     => $grandchild['name'],
							'href'     => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grandchild['category_id']),
						);
					}

					$children_data[] = array(
						'name'  => $child['name'],
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id']),
						'children' => $grandchildren_data,
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id']),
					'icon'     => $category['icon'] ? 'image/' . $category['icon'] : false,
				);
			}
		}

		return $this->load->view('common/menu', $data);
	}
}
