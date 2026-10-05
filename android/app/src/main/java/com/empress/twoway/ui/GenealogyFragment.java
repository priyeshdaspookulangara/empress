package com.empress.twoway.ui;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.empress.twoway.R;
import com.empress.twoway.adapters.DownlineAdapter;
import com.empress.twoway.models.DownlineMember;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class GenealogyFragment extends Fragment {

    private SwipeRefreshLayout swipeRefresh;
    private TextView tvSelfName, tvSelfMemberId;
    private TextView tvPos1Name, tvPos2Name, tvPos3Name;
    private RecyclerView rvDownlines;

    private SessionManager sessionManager;
    private final List<DownlineMember> downlineList = new ArrayList<>();
    private DownlineAdapter adapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_genealogy, container, false);

        sessionManager = new SessionManager(requireContext());

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        tvSelfName = view.findViewById(R.id.tvSelfName);
        tvSelfMemberId = view.findViewById(R.id.tvSelfMemberId);
        tvPos1Name = view.findViewById(R.id.tvPos1Name);
        tvPos2Name = view.findViewById(R.id.tvPos2Name);
        tvPos3Name = view.findViewById(R.id.tvPos3Name);
        rvDownlines = view.findViewById(R.id.rvDownlines);

        rvDownlines.setLayoutManager(new LinearLayoutManager(requireContext()));
        adapter = new DownlineAdapter(downlineList);
        rvDownlines.setAdapter(adapter);

        swipeRefresh.setOnRefreshListener(this::loadGenealogyData);

        loadGenealogyData();

        return view;
    }

    private void loadGenealogyData() {
        swipeRefresh.setRefreshing(true);

        ApiClient.get("user/teams.php", sessionManager.getToken(), new ApiClient.ApiCallback() {
            @Override
            public void onSuccess(JSONObject response) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);

                if (response.optBoolean("success")) {
                    JSONObject data = response.optJSONObject("data");
                    if (data != null) {
                        // Matrix Tree
                        JSONObject tree = data.optJSONObject("matrix_tree");
                        if (tree != null) {
                            tvSelfName.setText(tree.optString("name", sessionManager.getName()));
                            tvSelfMemberId.setText(tree.optString("member_id", sessionManager.getMemberId()));

                            JSONObject childrenObj = tree.optJSONObject("children");
                            if (childrenObj != null) {
                                updatePosSlot(childrenObj.optJSONObject("1"), tvPos1Name);
                                updatePosSlot(childrenObj.optJSONObject("2"), tvPos2Name);
                                updatePosSlot(childrenObj.optJSONObject("3"), tvPos3Name);
                            }
                        }

                        // Downlines List
                        JSONArray downlineArray = data.optJSONArray("downline_6_levels");
                        downlineList.clear();
                        if (downlineArray != null) {
                            for (int i = 0; i < downlineArray.length(); i++) {
                                JSONObject dObj = downlineArray.optJSONObject(i);
                                if (dObj != null) {
                                    downlineList.add(new DownlineMember(
                                            dObj.optString("member_id"),
                                            dObj.optString("sponsor_id"),
                                            dObj.optString("name"),
                                            dObj.optString("package_type"),
                                            dObj.optInt("matrix_level", 1)
                                    ));
                                }
                            }
                        }
                        adapter.notifyDataSetChanged();
                    }
                } else {
                    Toast.makeText(requireContext(), response.optString("message", "Failed to load genealogy"), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onError(String errorMessage) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);
                Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void updatePosSlot(JSONObject child, TextView tvName) {
        if (child != null) {
            String name = child.optString("name", "Occupied");
            String mId = child.optString("member_id", "");
            tvName.setText(name + "\n(" + mId + ")");
            tvName.setTextColor(requireContext().getColor(R.color.white));
        } else {
            tvName.setText("Available\nSlot");
            tvName.setTextColor(requireContext().getColor(R.color.text_secondary));
        }
    }
}
