-- 0_ksf_em_campaign_lists
-- Which mailing lists a campaign is linked to.
--
-- WHY THIS TABLE IS MODULE-LOCAL:
--   The link between a campaign and a mailing list is EmailManager's own
--   concept (its campaign page links campaigns to the lists it sends to), so
--   the table belongs to the 0_ksf_em_* family.
--
-- The FA install engine replaces the literal 0_ with TB_PREF.
--
-- Note: `list_id` holds 0_ksf_em_mailing_lists.id.

CREATE TABLE IF NOT EXISTS `0_ksf_em_campaign_lists` (
    `campaign_id` INT(11) NOT NULL,
    `list_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`campaign_id`, `list_id`),
    KEY `idx_list` (`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
