<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_agent\AgentAccessControlHandler;
use Drupal\ps_agent\AgentListBuilder;
use Drupal\ps_agent\Form\AgentDeleteForm;
use Drupal\ps_agent\Form\AgentForm;

/**
 * Defines the Agent entity.
 *
 * Content entity representing a real estate agent with CRM synchronization
 * and BO-specific protected fields (email, phone, internal_notes).
 *
 * Performance: Indexed on external_id for import lookups.
 * Cache tags: agent:{id}, agent_list.
 *
 * @see \Drupal\ps_agent\Entity\AgentInterface
 * @see docs/modules/ps_agent.md
 * @see docs/02-modele-donnees-drupal.md#43-entité-ps_agent
 */
#[ContentEntityType(
  id: 'agent',
  label: new TranslatableMarkup('Agent'),
  label_collection: new TranslatableMarkup('Agents'),
  label_singular: new TranslatableMarkup('agent'),
  label_plural: new TranslatableMarkup('agents'),
  label_count: [
    'singular' => '@count agent',
    'plural' => '@count agents',
  ],
  handlers: [
    'list_builder' => AgentListBuilder::class,
    'access' => AgentAccessControlHandler::class,
    'form' => [
      'default' => AgentForm::class,
      'add' => AgentForm::class,
      'edit' => AgentForm::class,
      'delete' => AgentDeleteForm::class,
    ],
    'views_data' => 'Drupal\views\EntityViewsData',
  ],
  base_table: 'agent',
  data_table: 'agent_field_data',
  translatable: TRUE,
  admin_permission: 'administer agent entities',
  entity_keys: [
    'id' => 'id',
    'label' => 'last_name',
    'uuid' => 'uuid',
    'langcode' => 'langcode',
  ],
  links: [
    'canonical' => '/agent/{agent}',
    'add-form' => '/admin/ps/structure/agents/add',
    'edit-form' => '/admin/ps/structure/agents/{agent}/edit',
    'delete-form' => '/admin/ps/structure/agents/{agent}/delete',
    'collection' => '/admin/ps/structure/agents',
  ],
  field_ui_base_route: 'ps_agent.settings',
)]
final class Agent extends ContentEntityBase implements AgentInterface {

  /**
   * {@inheritdoc}
   */
  public function getExternalId(): ?string {
    return $this->get('external_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExternalId(string $externalId): static {
    $this->set('external_id', $externalId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName(): ?string {
    return $this->get('first_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName(): ?string {
    return $this->get('last_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(): ?string {
    return $this->get('email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhone(): ?string {
    return $this->get('phone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFax(): ?string {
    return $this->get('fax')->value;
  }

  /**
   * Gets the agent's display label.
   *
   * @return string
   *   The calculated display label (last_name + first_name).
   */
  public function label(): string {
    $lastName = $this->getLastName() ?? '';
    $firstName = $this->getFirstName() ?? '';
    $label = trim("$lastName $firstName");
    
    if (empty($label)) {
      return (string) new TranslatableMarkup('Agent @id', ['@id' => $this->id()]);
    }
    
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime(): int {
    return (int) $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // External CRM identifier.
    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('External ID'))
      ->setDescription(new TranslatableMarkup('The unique identifier from the CRM system.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // First name (CRM locked).
    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('First Name'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Last name (CRM locked).
    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Last Name'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Email (BO editable - preserved on CRM import).
    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'email_mailto',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Phone (BO editable).
    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(new TranslatableMarkup('Phone'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'telephone_link',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fax.
    $fields['fax'] = BaseFieldDefinition::create('telephone')
      ->setLabel(new TranslatableMarkup('Fax'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Photo (CRM locked).
    $fields['photo'] = BaseFieldDefinition::create('image')
      ->setLabel(new TranslatableMarkup('Photo'))
      ->setSettings([
        'file_directory' => 'agents/photos',
        'file_extensions' => 'png jpg jpeg',
        'max_filesize' => '2 MB',
        'max_resolution' => '800x800',
        'alt_field' => TRUE,
        'alt_field_required' => FALSE,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -20,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Title callback for entity routes.
   *
   * @param \Drupal\ps_agent\Entity\AgentInterface $agent
   *   The agent entity.
   *
   * @return string
   *   The agent display name.
   */
  public static function loadTitle(AgentInterface $agent): string {
    return $agent->label();
  }

}
