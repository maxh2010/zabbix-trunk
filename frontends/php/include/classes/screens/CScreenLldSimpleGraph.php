<?php
/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Shows surrogate screen filled with simple graphs generated by selected item prototype or preview of item prototype.
 */
class CScreenLldSimpleGraph extends CScreenLldGraphBase {

	/**
	 * @var array
	 */
	protected $createdItemIds = array();

	/**
	 * @var array
	 */
	protected $itemPrototype = null;

	/**
	 * Makes and returns simple graph screen items.
	 *
	 * @return array
	 */
	protected function getSurrogateScreenItems() {
		$createdItemIds = $this->getCreatedItemIds();
		return $this->getSimpleGraphsForSurrogateScreen($createdItemIds);
	}

	/**
	 * Retrieves items created for item prototype given as resource for this screen item
	 * and returns array of the item IDs, ordered by item name.
	 *
	 * @return array
	 */
	protected function getCreatedItemIds() {
		if (!$this->createdItemIds) {
			$itemPrototype = $this->getItemPrototype();

			if ($itemPrototype) {
				// get all created (discovered) items for current host
				$allCreatedItems = API::Item()->get(array(
					'output' => array('itemid', 'name'),
					'hostids' => array($itemPrototype['discoveryRule']['hostid']),
					'selectItemDiscovery' => array('itemid', 'parent_itemid'),
					'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CREATED),
				));

				// collect those item IDs where parent item is item prototype selected for this screen item as resource
				foreach ($allCreatedItems as $item) {
					if ($item['itemDiscovery']['parent_itemid'] == $itemPrototype['itemid']) {
						$this->createdItemIds[$item['itemid']] = $item['name'];
					}
				}
				natsort($this->createdItemIds);
				$this->createdItemIds = array_keys($this->createdItemIds);
			}
		}

		return $this->createdItemIds;
	}

	/**
	 * Makes and return simple graph screen items from given item IDs.
	 *
	 * @param array $itemIds
	 *
	 * @return array
	 */
	protected function getSimpleGraphsForSurrogateScreen(array $itemIds) {
		$screenItemTemplate = $this->getScreenItemTemplate(SCREEN_RESOURCE_SIMPLE_GRAPH);

		$screenItems = array();
		foreach ($itemIds as $itemId) {
			$screenItem = $screenItemTemplate;

			$screenItem['resourceid'] = $itemId;
			$screenItem['screenitemid'] = $itemId;
			$screenItem['url'] = $this->screenitem['url'];

			$screenItems[] = $screenItem;
		}

		return $screenItems;
	}

	/**
	 * Returns output for simple graph preview.
	 *
	 * @return CTag
	 */
	protected function getPreviewOutput() {
		$itemPrototype = $this->getItemPrototype();

		$queryParams = array(
			'items' => array($itemPrototype),
			'period' => 3600,
			'legend' => 1,
			'width' => $this->screenitem['width'],
			'height' => $this->screenitem['height'],
			'name' => $itemPrototype['hosts'][0]['name'].NAME_DELIMITER.$itemPrototype['name']
		);

		$src = 'chart3.php?'.http_build_query($queryParams);

		$img = new CImg($src);
		$img->preload();

		return new CSpan($img);
	}

	/**
	 * Resolves and retrieves effective item prototype used in this screen item.
	 *
	 * @return array|bool
	 */
	protected function getItemPrototype() {
		if ($this->itemPrototype === null) {
			$defaultOptions = array(
				'output' => array('itemid', 'name'),
				'selectHosts' => array('name'),
				'selectDiscoveryRule' => array('hostid')
			);

			$options = array();

			/*
			 * If screen item is dynamic or is templated screen, real item prototype is looked up by "key"
			 * used as resource ID for this screen item and by current host.
			 */
			if (($this->screenitem['dynamic'] == SCREEN_DYNAMIC_ITEM || $this->isTemplatedScreen) && $this->hostid) {
				$currentItemPrototype = API::ItemPrototype()->get(array(
					'output' => array('key_'),
					'itemids' => array($this->screenitem['resourceid'])
				));
				$currentItemPrototype = reset($currentItemPrototype);

				$options['hostids'] = array($this->hostid);
				$options['filter'] = array('key_' => $currentItemPrototype['key_']);
			}
			// otherwise just use resource ID given to to this screen item.
			else {
				$options['itemids'] = array($this->screenitem['resourceid']);
			}

			$options = zbx_array_merge($defaultOptions, $options);

			$selectedItemPrototype = API::ItemPrototype()->get($options);
			$this->itemPrototype = reset($selectedItemPrototype);
		}

		return $this->itemPrototype;
	}
}
