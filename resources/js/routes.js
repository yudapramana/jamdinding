
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
        path: 'master-branches-groups-categories',
        name: 'admin.master.branches-groups-categories',
        component: () => import('./pages/master/MasterBranchesGroupsCategories.vue'),
      },
      {
        path: 'master-list-fields',
        name: 'admin.master.list-fields',
        component: () => import('./pages/master/MasterListFields.vue'),
      },
      {
        path: 'master-branches',
        name: 'admin.master.branches',
        component: () => import('./pages/master/MasterBranches.vue'),
      },
      {
        path: 'master-groups',
        name: 'admin.master.groups',
        component: () => import('./pages/master/MasterGroups.vue'),
      },
      {
        path: 'master-categories',
        name: 'admin.master.categories',
        component: () => import('./pages/master/MasterCategories.vue'),
      },
      {
        path: 'master-field-components',
        name: 'admin.master.field-components',
        component: () => import('./pages/master/MasterFieldComponents.vue'),
      },
      {
        path: 'event-branches',
        name: 'admin.event.branches',
        component: () => import('./pages/event/EventBranches.vue'),
      },
      {
        path: 'event-groups',
        name: 'admin.event.groups',
        component: () => import('./pages/event/EventGroups.vue'),
      },
      {
        path: 'event-categories',
        name: 'admin.event.categories',
        component: () => import('./pages/event/EventCategories.vue'),
      },
      {
        path: 'event-field-components',
        name: 'admin.event.field-components',
        component: () => import('./pages/event/EventFieldComponents.vue'),
      },
      {
        path: 'event-participants/bank-data',
        name: 'admin.event.participants.bank-data',
        component: () => import('./pages/EventParticipants.vue'),
      },
      {
        path: '/event-participants/registration/:status',
        name: 'admin.event.participants.registration',
        component: () => import('./pages/EventParticipantsRegistration.vue'),
        props: true, // kirim param status ke props
      },
      {
        path: '/event-participants/reregistration',
        name: 'admin.event.participants.reregistration',
        component: () => import('./pages/EventParticipantsReregistration.vue'),
      },
      {
        path: '/event-participants/final',
        name: 'admin.event.participants.final',
        component: () => import('./pages/EventParticipantsFinal.vue'),
      },


      // batas
      {
        path: '/participants/status/:status',
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
        path: 'event-stage',
        name: 'admin.eventstagelist',
        component: () => import('./pages/event/EventStageList.vue'),
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
        path: 'master-permission-role',
        name: 'admin.master-permission-role',
        component: () => import('./pages/permission/PermissionRoleList.vue'),
      },
      {
        path: 'event-competition-branch',
        name: 'admin.event-competition-branch',
        component: () => import('./pages/competition/EventCompetitionBranchList.vue'),
      },
      {
        path: 'event-users',
        name: 'admin.event-users',
        component: () => import('./pages/event/EventUserList.vue'),
      },
      {
        path: 'events',
        name: 'admin.events',
        component: () => import('./pages/event/EventList.vue'),
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
