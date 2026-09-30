-- ============================================================================
-- ksf_FA_EmailManager upgrade — table prefix correction
-- ============================================================================
-- Module tables were created as 0_fa_em_* which does not match the documented
-- convention in AGENTS.md ("SQL tables: 0_ksf_<tablename>"). The 0_fa_ prefix
-- also read as "FrontAccounting's own table" rather than a ksf module table.
--
-- The per-table install files now create 0_ksf_em_*; these statements retire
-- the misnamed tables.
--
-- NOTE: ksf_FA_CRM's #25 handover copies 0_fa_crm_email_accounts into
-- 0_ksf_em_accounts. CRM's activate_extension() must run before this file, or
-- activate EmailManager again afterwards to retry the handover.
--
-- TEMPORARY: these drops are destructive and are removed once every
-- installation has been cut over.
-- ============================================================================

DROP TABLE IF EXISTS `0_fa_em_routes`;
DROP TABLE IF EXISTS `0_fa_em_subscribers`;
DROP TABLE IF EXISTS `0_fa_em_mailing_lists`;
DROP TABLE IF EXISTS `0_fa_em_inbound_emails`;
DROP TABLE IF EXISTS `0_fa_em_accounts`;
