**CONTROLLER**

changestate(from):
	On c_setstate() - from state in params, current state will be available in $gp->c_project

ccreatereview(idprojectsreviewtypes, reviewtype, description):
	See c_createreview();

ccreatesubmittalphase(idsubmittalphase, mode, itemorder, description):
	See c_createsubmittalphase();

returnapplication(note):
	On application returned

**CHECKLISTS**

checklistsaved(items):
	On checklist saved

**FORMS**

formsaved(idform):
	On form saved


**INSPECTIONS**

inspectionassign(idinspector, idschedule, datetime):
	On assign inspection, call from inspections_schedule_notify()

inspectionreschedule(idinspector, idschedule, datetime, olddatetime):
	On reschedule inspection, call from inspections_schedule_notify()

**INTERSTITIALS**

interstitialactivated(idinterstitial):
	On interstitial activated

**PLAN REVIEW**

approvalissued(idsubmittalphase, idsubmittal, idsprt, docs, notify):
	On review approval issued

approvalsubmittal(submittal, phase, docs):
	On comment submittal in mod_finalize_submittal() - submittal and phase params are full DB rows

couploaded(docs):
	On certificate of occupancy docs uploaded

codeinspuploaded(docs):
	On code inspection docs uploaded

commentissued(idsubmittalphase, idsubmittal, idsprt, docs, notify):
	On review comment issued

commentsubmittal(submittal, phase, docs):
	On comment submittal in mod_finalize_submittal() - submittal and phase params are full DB rows

docsuploaded(docs):
	On misc. docs uploaded

finalsetrequired(phase):
	On final set required - phase param is a full projectsubmittalphase DB row

firstsubmittal(submittal, phase, docs, ishardcopy):
	On first submittal completed - submittal and phase params are full DB rows

finalsubmittal(submittal, phase, docs, ishardcopy):
	On phase final submittal completed - submittal and phase params are full DB rows

permdocsrequired(phase):
	On permitted docs. required - phase param is a full projectsubmittalphase DB row

permdocscomplete(phase):
	On permitted docs. completed - phase param is a full projectsubmittalphase DB row

permdocssubmittal(submittal, phase, docs, ishardcopy):
	On perm docs. submittal completed - submittal and phase params are full DB rows

phaseallapproved(phase):
	On all review approved for phase - phase param is a full projectsubmittalphase DB row

reviewassigned(reviews, users, to):
	On review assigned

reviewnouser(idsprt):
	On reviewer unset for review

reviewremoved(reviews, user, to):
	On review removed

specialinspuploaded(docs):
	On special inspection docs uploaded

submittalcomplete(submittal, phase, docs, ishardcopy):
	On submittal complete - submittal and phase params are DB rows

submittaldocsuploaded(idsubmittal, docs, ishardcopy):
	On submittal docs uploaded

structobsuploaded(docs):
	On struct. obs. documents uploaded

syncedallapprovalsissued(sprt, submittalapprovals, reviews):
	With review syncing active, on all approvals issued - sprt param is a full submittalphases_reviewtypes DB row

syncedallcommentsissued(sprt, submittalapprovals, reviews):
	With review syncing active, on all comments issued - sprt param is a full submittalphases_reviewtypes DB row

syncedapprovalpending(idsubmittal, phase):
	With review syncing active, on approval pending issuance - phase param is projectsubmittalphase DB row

syncedcommentpending(idsubmittal, phase):
	With review syncing active, on comment pending issuance - phase param is projectsubmittalphase DB row

syncedgroupcomplete(phase, idsubmittal, reviews, submittalcomments, submittalapprovals):
	With review syncing active, on completion of a synced group - phase param is a full projectsubmittalphase DB row

syncedgrouppending(phase, idsubmittal, reviews, submittalcomments, submittalapprovals):
	With review syncing active, on reviews pending - phase param is a full projectsubmittalphase DB row

uploaddeleted(upload):
	On upload deleted - upload is uploads DB row

**PERMITS**
permitstatusupdated(status, comment):
	On permit status updated

**ROUTING**

routeformcomplete(idroute):
	On route form complete

**TIMERS**

timercheck(timer):
	On project_timers check - timer is project_timers DB row

**USERS**

inviteactive(iduser, email):
	On invite_to_project() for active user - ID and email of user

inviteinactive(iduser, email):
	On invite_to_project() for inactive user - ID and email of user

invitenew(iduser, email):
	On invite_to_project() success for new user - ID and email of user


**OTHER**

projectdeleted(idproject):
	On project deletion

**SYSTEM**

project_created(idproject, idworkflow):
	On new project created

user_registered(iduser, idrole):
	On new user registered

