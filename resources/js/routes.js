
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
        path: '/participants/status/:status',
        name: 'admin.participants.status',
        component: () => import('./pages/participant/ParticipantStatusList.vue'),
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
