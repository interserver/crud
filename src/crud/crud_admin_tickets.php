<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_admin_tickets()
 * @return void
 */
function crud_admin_tickets()
{
	Crud::init("select swtickets.subject
, swtickets.ticketid
, swtickets.ticketmaskid
, swtickets.lastreplier
, swtickets.totalreplies
, swtickets.lastactivity
, swtickets.duetime
, swticketpriorities.title AS priority
, swdepartments.title AS department
, swticketstatus.title AS status
, swemailqueues.email AS emailqueue
, swstaff.fullname AS staff_name
, swtickets.assignstatus AS assigned
, swtickets.fullname
, swtickets.email
, swtickets.dateline
, swslaplans.title AS slaplan
, swtickets.hasattachments
, swtickets.hasnotes
, swtickets.timeworked
, swtickets.lastpostid
 FROM
  swtickets
LEFT OUTER JOIN swticketstatus
ON swtickets.ticketstatusid = swticketstatus.ticketstatusid
LEFT OUTER JOIN swticketpriorities
ON swticketpriorities.priorityid = swtickets.priorityid
LEFT OUTER JOIN swdepartments
ON swtickets.departmentid = swdepartments.departmentid
LEFT OUTER JOIN swemailqueues
ON swtickets.emailqueueid = swemailqueues.emailqueueid
LEFT OUTER JOIN swusers
ON swtickets.userid = swusers.userid
LEFT OUTER JOIN swstaff
ON swtickets.ownerstaffid = swstaff.staffid
LEFT OUTER JOIN swslaplans
ON swtickets.slaplanid = swslaplans.slaplanid
WHERE
  swtickets.ticketstatusid = 4
  AND swtickets.departmentid IN (SELECT swstaffassigns.departmentid
								 FROM
								   swstaff
								 LEFT OUTER JOIN swstaffassigns
								 ON swstaffassigns.staffid = swstaff.staffid
								 WHERE
								   swstaff.email = 'detain@interserver.net')", 'helpdesk')
		->set_title('Admin Tickets')
		->go();
}
