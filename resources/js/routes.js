
export default [
  {
    path: '/landing',
    name: 'app.landing',
    component: () => import('./pages/Landing.vue'),
  },
  {
    path: '/login',
    name: 'app.login',
    component: () => import('./pages/auth/Login.vue'),
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('./pages/auth/ResetPassword.vue') // sesuaikan path
  },
  {
    path: '/admin',
    meta: { requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: () => import('./components/Dashboard.vue'),
      },
      {
        path: 'core/branches-groups-categories',
        name: 'admin.core.branches-groups-categories',
        component: () => import('./pages/core/CoreBranchesGroupsCategories.vue'),
      },
      {
        path: 'core/fields',
        name: 'admin.core.fields',
        component: () => import('./pages/core/CoreListFields.vue'),
      },
      {
        path: 'core/permissions',
        name: 'admin.core.permissions',
        component: () => import('./pages/permission/PermissionRoleList.vue'),
      },
      {
        path: 'master/branches',
        name: 'admin.master.branches',
        component: () => import('./pages/master/MasterBranches.vue'),
      },
      {
        path: 'master/groups',
        name: 'admin.master.groups',
        component: () => import('./pages/master/MasterGroups.vue'),
      },
      {
        path: 'master/categories',
        name: 'admin.master.categories',
        component: () => import('./pages/master/MasterCategories.vue'),
      },
      {
        path: 'master/field-components',
        name: 'admin.master.field-components',
        component: () => import('./pages/master/MasterFieldComponents.vue'),
      },
      {
        path: 'events/index',
        name: 'admin.events.index',
        component: () => import('./pages/event/EventList.vue'),
      },
      {
        path: 'event/stages',
        name: 'admin.event.stages',
        component: () => import('./pages/event/EventStageList.vue'),
      },
      {
        path: 'event/branches',
        name: 'admin.event.branches',
        component: () => import('./pages/event/EventBranches.vue'),
      },
      {
        path: 'event/groups',
        name: 'admin.event.groups',
        component: () => import('./pages/event/EventGroups.vue'),
      },
      {
        path: 'event/categories',
        name: 'admin.event.categories',
        component: () => import('./pages/event/EventCategories.vue'),
      },
      {
        path: 'event/users',
        name: 'admin.event.users',
        component: () => import('./pages/event/EventUserList.vue'),
      },
      {
        path: 'event/participants/bank-data',
        name: 'admin.event.participants.bank-data',
        component: () => import('./pages/EventParticipants.vue'),
      },
      {
        path: 'event/participants/registration/:status',
        name: 'admin.event.participants.registration',
        component: () => import('./pages/EventParticipantsRegistration.vue'),
        props: true, // kirim param status ke props
      },
      {
        path: 'event/participants/reregistration',
        name: 'admin.event.participants.reregistration',
        component: () => import('./pages/EventParticipantsReregistration.vue'),
      },
      {
        path: 'event/participants/final',
        name: 'admin.event.participants.final',
        component: () => import('./pages/EventParticipantsFinal.vue'),
      },
      {
        path: 'event/judges/users',
        name: 'admin.event.judges.users',
        component: () => import('./pages/event/EventJudgesUsers.vue'),
      },
      {
        path: 'event/judges/panels',
        name: 'admin.event.judges.panel',
        component: () => import('./pages/event/EventJudgePanels.vue'),
      },
      {
        path: 'event/scoring/field-components',
        name: 'admin.event.scoring.field-components',
        component: () => import('./pages/event/EventFieldComponents.vue'),
      },
      {
        path: 'event/scoring/index',
        name: 'admin.event.scoring.index',
        component: () => import('./pages/event/EventCompetitionsTree.vue'),
      },

      // ==============================
      // EVENT COMPETITION – SCORING
      // ==============================

      // Form pengisian nilai 1 peserta pada 1 kompetisi
      {
        path: 'event/scoring/input-default',
        name: 'admin.event.scoring.input-default',
        component: () => import('./pages/event/EventCompetitionScoring.vue'),
      },
      {
        path: 'event/scoring/:id/input-specific',
        name: 'admin.event.scoring.input-specific',
        component: () => import('./pages/event/EventCompetitionScoring.vue'),
        props: route => ({
          eventParticipantId: route.query.event_participant_id || null,
        }),
      },
      
      // selector page (tanpa :id)
      {
        path: 'event/scores/select',
        name: 'admin.event.scores.select',
        component: () => import('./pages/event/EventCompetitionScores.vue'),
      },

      // detail per competition (dengan :id)
      {
        path: 'event/:id/scores/index',
        name: 'admin.event.scores.index',
        component: () => import('./pages/event/EventCompetitionScores.vue'),
      },

      // selector (tanpa :id)
      {
        path: 'event/scores/detail/select',
        name: 'admin.event.scores.detail.select',
        component: () => import('./pages/event/EventCompetitionScoresDetail.vue'),
      },

      // detail (dengan :id)
      {
        path: '/admin/event/:id/scores/detail/index',
        name: 'admin.event.scores.detail.index',
        component: () => import('./pages/event/EventCompetitionScoresDetail.vue'),
      },

      // Ranking selector (tanpa :id)
      {
        path: '/admin/event/ranking/select',
        name: 'admin.event.ranking.select',
        component: () => import('./pages/event/EventCompetitionRanking.vue'),
      },

      // Ranking detail (dengan :id)
      {
        path: '/admin/event/:id/ranking/index',
        name: 'admin.event.ranking.index',
        component: () => import('./pages/event/EventCompetitionRanking.vue'),
      },

      {
        path: '/admin/event/results/index',
        name: 'admin.event.results.index',
        component: () => import('./pages/event_contingents/EventContingentsIndex.vue'),
        meta: {
          title: 'Perolehan Juara Kontingen',
        },
      },










      // batas
      {
        path: 'participants/status/:status',
        name: 'admin.participants.status',
        component: () => import('./pages/participant/ParticipantStatusList.vue'),
        props: true, // kirim param status ke props
      },
      {
        path: 'event-participant-registrations',
        name: 'admin.event.participant-registrations',
        component: () => import('./pages/EventParticipantsRegistration.vue'),
      },
      
      {
        path: 'master-stage',
        name: 'admin.master-stage',
        component: () => import('./pages/event/MasterStageList.vue'),
      },
      {
        path: 'master-competition-group',
        name: 'admin.master-competition-group',
        component: () => import('./pages/competition/MasterCompetitionGroupList.vue'),
      },
      {
        path: 'master-competition-category',
        name: 'admin.master-competition-category',
        component: () => import('./pages/competition/MasterCompetitionCategoryList.vue'),
      },
      {
        path: 'master-competition-branch',
        name: 'admin.master-competition-branch',
        component: () => import('./pages/competition/MasterCompetitionBranchList.vue'),
      },
      {
        path: 'event-competition-branch',
        name: 'admin.event-competition-branch',
        component: () => import('./pages/competition/EventCompetitionBranchList.vue'),
      },
      
      
      {
        path: 'participants',
        name: 'admin.participants',
        component: () => import('./pages/participant/ParticipantList.vue'),
      },
      {
        path: '/participants/reregister',
        name: 'admin.participants.reregister',
        component: () => import('./pages/participant/ParticipantReregisterList.vue'),
        props: true, // kirim param status ke props
      },
      {
        path: 'vervals',
        name: 'admin.vervals',
        component: () => import('./pages/vervals/VervalList.vue'),
      },
      {
        path: 'verval-history',
        name: 'admin.verval-history',
        component: () => import('./pages/vervals/VervalHistory.vue'),
      },
      {
        path: 'monitor-workout',
        name: 'admin.workunits.monitor',
        component: () => import('./pages/workunits/WorkUnitMonitor.vue'),
      },
      {
        path: 'workunits',
        name: 'admin.workunits',
        component: () => import('./pages/workunits/WorkUnitList.vue'),
      },
      {
        path: 'reports',
        name: 'admin.reports',
        component: () => import('./pages/reports/ListReports.vue'),
      },
      {
        path: 'org-reports',
        name: 'admin.orgreports',
        component: () => import('./pages/org_reports/OrgReports.vue'),

      },
      {
        path: 'users',
        name: 'admin.users',
        component: () => import('./pages/users/UserList.vue'),
      },
      {
        path: 'admins',
        name: 'admin.admins',
        component: () => import('./pages/admins/AdminList.vue'),
      },
      {
        path: 'users/:id/documents',
        name: 'admin.user.documents',
        component: () => import('./pages/docs/UserDocs.vue'),
      },
      {
        path: 'docusers',
        name: 'admin.doc.users',
        component: () => import('./pages/users/UserDocList.vue'),
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: () => import('./pages/settings/UpdateSetting.vue'),
      },
      {
        path: 'profile',
        name: 'admin.profile',
        component: () => import('./pages/profile/UpdateProfile.vue'),
      },
      {
        path: 'docprogress',
        name: 'admin.doc.progress',
        component: () => import('./pages/progress/DocProgress.vue'),
      },
    ],
  },
  {
    path: '/user',
    children: [
      {
        path: 'dashboard',
        name: 'user.dashboard',
        component: () => import('./components/UserDashboard.vue'),
      },
      {
        path: 'profile',
        name: 'user.profile',
        component: () => import('./pages/profile/UserProfile.vue'),
      },
      {
        path: 'change-password',
        name: 'user.change-password',
        component: () => import('./pages/profile/ChangePassword.vue'),
      },
      {
        path: 'docs',
        name: 'user.docs',
        component: () => import('./pages/docs/MyDocs.vue'),
      },
      {
        path: 'upload',
        name: 'user.upload',
        component: () => import('./pages/docs/UserUploadDoc.vue'),
      },
      // {
      //   path: 'flipbook',
      //   name: 'user.flipbook',
      //   component: () => import('./pages/flipbook/FlipBookViewer.vue'),
      // },
    ],
  },
  { path: '/:pathMatch(.*)*', 
    name: 'not-found', 
    component: () => import('./components/NotFound.vue'), 
  },
];
