import React, { createContext, useContext, useState, useEffect } from 'react';
import { workspaceAPI } from '../services/api';
import { useAuth } from './AuthContext';
import toast from 'react-hot-toast';

const WorkspaceContext = createContext(null);

export const useWorkspace = () => {
  const context = useContext(WorkspaceContext);
  if (!context) {
    throw new Error('useWorkspace must be used within a WorkspaceProvider');
  }
  return context;
};

export const WorkspaceProvider = ({ children }) => {
  const { user, loading: authLoading } = useAuth();
  const [workspaces, setWorkspaces] = useState([]);
  const [currentWorkspace, setCurrentWorkspace] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Only load workspaces if user is authenticated
    if (user && !authLoading) {
      loadWorkspaces();
    } else if (!authLoading) {
      // If not authenticated, clear workspaces and stop loading
      setWorkspaces([]);
      setCurrentWorkspace(null);
      setLoading(false);
    }
  }, [user, authLoading]);

  const loadWorkspaces = async () => {
    if (!user) {
      setLoading(false);
      return;
    }
    
    try {
      setLoading(true);
      const response = await workspaceAPI.getAll();
      setWorkspaces(response.data.data || response.data);
      
      // Set first workspace as current if none selected
      const savedWorkspaceId = localStorage.getItem('currentWorkspaceId');
      if (savedWorkspaceId) {
        const workspace = (response.data.data || response.data).find(w => w.id === parseInt(savedWorkspaceId));
        if (workspace) {
          setCurrentWorkspace(workspace);
        } else if ((response.data.data || response.data).length > 0) {
          setCurrentWorkspace((response.data.data || response.data)[0]);
        }
      } else if ((response.data.data || response.data).length > 0) {
        setCurrentWorkspace((response.data.data || response.data)[0]);
      }
    } catch (error) {
      console.error('Failed to load workspaces:', error);
      toast.error('Failed to load workspaces');
    } finally {
      setLoading(false);
    }
  };

  const selectWorkspace = (workspace) => {
    setCurrentWorkspace(workspace);
    localStorage.setItem('currentWorkspaceId', workspace.id);
  };

  const createWorkspace = async (data) => {
    try {
      const response = await workspaceAPI.create(data);
      const newWorkspace = response.data.data || response.data;
      setWorkspaces([...workspaces, newWorkspace]);
      setCurrentWorkspace(newWorkspace);
      toast.success('Workspace created successfully!');
      return { success: true, data: newWorkspace };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to create workspace';
      toast.error(message);
      return { success: false, error: message };
    }
  };

  const updateWorkspace = async (id, data) => {
    try {
      const response = await workspaceAPI.update(id, data);
      const updatedWorkspace = response.data.data || response.data;
      setWorkspaces(workspaces.map(w => w.id === id ? updatedWorkspace : w));
      if (currentWorkspace?.id === id) {
        setCurrentWorkspace(updatedWorkspace);
      }
      toast.success('Workspace updated successfully!');
      return { success: true, data: updatedWorkspace };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to update workspace';
      toast.error(message);
      return { success: false, error: message };
    }
  };

  const deleteWorkspace = async (id) => {
    try {
      await workspaceAPI.delete(id);
      setWorkspaces(workspaces.filter(w => w.id !== id));
      if (currentWorkspace?.id === id) {
        setCurrentWorkspace(workspaces.find(w => w.id !== id) || null);
      }
      toast.success('Workspace deleted successfully!');
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to delete workspace';
      toast.error(message);
      return { success: false, error: message };
    }
  };

  const value = {
    workspaces,
    currentWorkspace,
    loading,
    selectWorkspace,
    createWorkspace,
    updateWorkspace,
    deleteWorkspace,
    refreshWorkspaces: loadWorkspaces,
  };

  return <WorkspaceContext.Provider value={value}>{children}</WorkspaceContext.Provider>;
};

